document.addEventListener('DOMContentLoaded', () => {
    const sidebar = new Sidebar('.lighterlms.course-sidebar');
    window.loadLesson = sidebar.loadLesson.bind(sidebar);

    const urlParams = new URLSearchParams(window.location.search);
    const initLesson = urlParams.get('lesson');
    if (!initLesson) {
        window.history.pushState({ lesson: null, lessonId: null }, '', window.location.href);
    }
});

window.onpopstate = async (e) => {
    if (e.state && e.state.lesson && e.state.lessonId) {
        await loadLesson(e.state.lessonId);
    }
};

class Sidebar {
    STALETIME = 120000; // 2 Minutes
    CACHETIME = 3600000; // 60 Minutes

    /** @type {HTMLElement} */
    el;
    /** @type {Set<HTMLButtonElement>} */
    topics;
    /** @type {Set<HTMLAnchorElement>} */
    lessons;

    constructor(selector) {
        this.el = document.querySelector(selector);
        if (!this.el) {
            console.warn('Could not initialize sidebar. No sidebar found at selector', selector);
            return;
        }

        this.topics = new Set(this.el.querySelectorAll('button'));
        this.lessons = new Set(this.el.querySelectorAll('.course-lesson'));
        this.state = new Map();
        this.cache = new Map();

        this.attachListener();
    }

    attachListener() {
        this.el.addEventListener('mousedown', this.handleMousedown.bind(this));
        this.el.addEventListener('click', this.handleClick.bind(this));
    }

    /** @param {MouseEvent} e */
    handleMousedown(e) {
        if (!e.isTrusted || !this.lessons.has(e.target)) return;
        const button = e.target;
        const id = button.dataset.lessonId;
        if (!this.state.has(button)) {
            const fetchPromise = this.getLesson(id)
            this.state.set(button, { fetchPromise, resolvedContent: null });

            document.addEventListener('mouseup', (mouseupE) => {
                if (!button.contains(mouseupE.target)) {
                    this.state.delete(button);
                }
            }, { once: true });
        }
    }

    /** @param {PointerEvent} e */
    handleClick(e) {
        if (this.topics.has(e.target)) {
            this.toggleTopic(e.target);
            return;
        }

        if (!this.lessons.has(e.target)) {
            return;
        }

        this.handleLessonClick(e, e.target);
    }

    /** @param {HTMLButtonElement} el  */
    toggleTopic(el) {
        const topicEl = el.closest('.lighter-topic')
        const list = topicEl?.querySelector('.course-lessons');
        if (!list) return;

        list.classList.toggle('open');
        el.ariaExpanded = !!list.classList.contains('open');
    }

    gcCache() {
        const now = Date.now();
        for (const [id, entry] of this.cache.entries()) {
            if (now - entry.timestamp > this.CACHETIME) {
                this.cache.delete(id);
            }
        }
    }

    isStale(entry) {
        return Date.now() - entry.timestamp >= this.STALETIME;
    }

    async getLesson(id) {
        const apiFetch = window.wp.apiFetch;
        if (!apiFetch) throw new Error('Could not load apiFetch');

        let entry = this.cache.get(id);
        const now = Date.now();

        if (entry && !this.isStale(entry) && entry.status === 'success') {
            return entry.data;
        }

        if (!entry) {
            entry = { data: null, timestamp: now, status: 'loading' };
            this.cache.set(id, entry);
        } else if (entry.status !== 'loading') {
            entry.status = 'loading';
        }

        const course_id = document.getElementById('main').dataset.courseId ?? 0;

        let lesson;
        try {
            lesson = await apiFetch({
                path: `lighterlms/v1/lesson/${id}?content_only=true`,
                method: 'GET',
                headers: {
                    course: course_id
                }
            });

            if (lesson.id != id) {
                throw new Error(`Tried to get content for lesson ${id}, but instead got ${lesson.id}`);
            }

            this.cache.set(id, {
                data: lesson,
                timestamp: now,
                status: 'success'
            });
        } catch (err) {
            if (entry && entry.data && this.isStale(entry)) {
                entry.status = "error";
                entry.error = err;
                return entry.data;
            }

            throw err;
        }

        return lesson;
    }

    /** @param {number} id */
    async loadLesson(id) {
        const btn = document.querySelector('[data-lesson-id="' + id + '"]');
        const btnState = this.state.get(btn);
        const entry = this.cache.get(id.toString());
        if (entry && !this.isStale(entry) && entry.status === 'success') {
            const lesson = entry.data;
            try {
                await this.handleLessonSuccess(lesson, id, btn);
            } catch (err) {
                this.handleLessonError(err, id);
                this.insertContent('', '', id);
            } finally {
                if (btnState) this.state.delete(btn);
                this.gcCache();
            }
        }

        // TODO: Fetch lesson if stale or no data.
    }

    /** @param {PointerEvent} e */
    async handleLessonClick(e) {
        e.preventDefault();

        const btn = e.target;
        const btnState = this.state.get(btn);
        const id = btn.dataset.lessonId;

        let entry = this.cache.get(id);
        if (entry && !this.isStale(entry) && entry.status === 'success') {
            const lesson = entry.data;
            try {
                await this.handleLessonSuccess(lesson, id, btn);
            } catch (err) {
                this.handleLessonError(err, id, btn);
                this.insertContent('', '', id);
            } finally {
                if (btnState) this.state.delete(btn);
                this.gcCache();
            }
            return;
        }

        let fetchPromise;
        if (btnState) {
            if (btnState.resolvedContent) {
                fetchPromise = Promise.resolve(btnState.resolvedContent);
            } else {
                fetchPromise = btnState.fetchPromise;
            }
        } else {
            fetchPromise = this.getLesson(id);
        }

        if (!entry || entry.status !== "loading") {
            const now = Date.now();
            this.cache.set(id, { data: null, timestamp: now, status: "loading" });
        }

        this.insertContent('', '', id);

        try {
            const lesson = await fetchPromise;
            await this.handleLessonSuccess(lesson, id, btn);
        } catch (err) {
            this.handleLessonError(err, id, btn);
            this.insertContent('', '', id);
        } finally {
            if (btnState) this.state.delete(btn);
            this.gcCache();
        }
    }

    async handleLessonSuccess(lesson, id) {
        const entry = this.cache.get(id);
        const wasStale = entry && this.isStale(entry);

        if (lesson.styles && lesson.styles.length > 0) {
            await this.loadStyles(lesson.styles);
        }

        const url = new URL(window.location.href);
        url.searchParams.set('lesson', lesson.slug?.toLowerCase());
        window.history.pushState({ lesson: lesson.title, lessonId: lesson.id }, "", url);

        this.insertContent(lesson.content, lesson.builder, id);

        if (wasStale) {
            this.getLesson(id).then((fresh) => {
                this.insertContent(fresh.content, fresh.builder, id);
            });
        }
    }

    handleLessonError(err, id, btn) {
        const entry = this.cache.get(id);
        entry.status = "error";
        entry.error = err;
    }

    loadStyles(styleUrls) {
        return Promise.all(
            styleUrls.map(url => {
                return new Promise((resolve, reject) => {
                    if (document.querySelector(`link[href="${url}"]`)) {
                        resolve();
                        return;
                    }

                    const link = document.createElement('link');
                    link.rel = 'stylesheet';
                    link.href = url;
                    link.onload = resolve;
                    link.onerror = reject;
                    document.head.appendChild(link);
                });
            })
        );
    }

    insertContent(html, builder, id) {
        const contentArea = document.getElementById('the-content');

        if (!contentArea) {
            throw new Error('Failed to insert content. No content area found!');
        }

        const entry = this.cache.get(id);
        let content = html;

        if (html === '' || html == null) {
            if (entry && entry.status == "loading") {
                content = '<div class="lighter-loading">Loading...</div>';
            } else if (entry && entry.status == "error") {
                content = '<div class="lighter-fetch-err">Error loading lesson. Please try again</div>';
            } else {
                content = '';
            }
        }

        contentArea.innerHTML = '<div class="lighter-lesson-wrap">' + content + '</div>';

        if (entry.status == "success") {
            window.scrollTo({ top: contentArea.getBoundingClientRect().y + window.scrollY - 20, behavior: 'smooth' });
        }
    }
}
