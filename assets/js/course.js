document.addEventListener('DOMContentLoaded', () => {
    new sidebarBtn('.course-lesson');
    document.querySelectorAll('.course-nav li:not(:first-of-type)').forEach(topic => new sidebarTopic(topic));
});

onpopstate = (e) => {
    if (e.state && e.state.lesson && e.state.lessonId) {
        loadLesson(e.state.lessonId);
    } else {
        // Fallback load default page
        window.location.href = window.location.href;
    }
};

class sidebarTopic {
    constructor(el) {
        const btn = el.querySelector('button');
        this.btn = btn;
        this.isOpen = btn.ariaExpanded;
        this.list = el.querySelector('.course-lessons');

        this.attachListeners();
    }

    attachListeners() {
        this.btn.addEventListener('click', this.toggleOpen.bind(this));
    }

    toggleOpen() {
        this.isOpen = !this.isOpen;
        this.list.classList.toggle('open');
        this.btn.ariaExpanded = this.isOpen;
    }
}

class sidebarBtn {
    constructor(selector) {
        this.buttons = document.querySelectorAll(selector);
        this.state = new Map();
        this.cache = new Map();
        this.staleTime = 0;
        this.cacheTime = 60 * 60 * 1000;
        this.attachListeners();
    }

    attachListeners() {
        this.buttons.forEach((btn) => {
            btn.addEventListener('mousedown', this.handleMouseDown.bind(this));
            btn.addEventListener('click', this.handleBtnClick.bind(this));
        });
    }

    gcCache() {
        const now = Date.now();
        for (const [id, entry] of this.cache.entries()) {
            if (now - entry.timestamp > this.cacheTime) {
                this.cache.delete(id);
            }
        }
    }

    isStale(entry) {
        return Date.now - entry.timestamp >= this.staleTime;
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

        let lesson;
        try {
            lesson = await apiFetch({
                path: `lighterlms/v1/lesson/${id}?content_only=true`,
                method: 'GET'
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

    /*
    async fetchContent(button) {
        const apiFetch = window.wp.apiFetch;
        const id = button.dataset.lessonId;
        if (!apiFetch) throw new Error('Could not load apiFetch');

        return apiFetch({
            path: `lighterlms/v1/lesson/${id}?content_only=true`,
            method: 'GET',
        });
    }
    */

    handleMouseDown(e) {
        if (!e.isTrusted) return;
        const button = e.currentTarget;
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

    async handleBtnClick(e) {
        e.preventDefault();

        const btn = e.currentTarget;
        const btnState = this.state.get(btn);
        const id = btn.dataset.lessonId;

        if (!btnState) {
            try {
                const lesson = await this.getLesson(id);
                await this.handleLessonSuccess(lesson, id, btn);
            } catch (err) {
                this.handleLessonError(err, id, btn);
            } finally {
                this.gcCache();
            }
            return;
        }

        try {
            let lesson;
            if (btnState.resolvedContent) {
                lesson = btnState.resolvedContent;
            } else {
                lesson = await btnState.fetchPromise;
            }

            await this.handleLessonSuccess(lesson, id, btn);
        } catch (err) {
            this.handleLessonError(err, id, btn);
        } finally {
            this.state.delete(btn);
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
        url.searchParams.set('lesson', lesson.title.toLowerCase());
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

        if (entry && entry.status == "loading") {
            content = '<div class="loading">Loading...</div>';
        } else if (entry && entry.status == "error") {
            content = '<div class="fetch-err">Error loading lesson. Please try again</div>';
        }

        contentArea.innerHTML = '<div class="lesson-wrap">' + content + '</div>';
    }
}
