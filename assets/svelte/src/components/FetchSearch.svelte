<script lang="ts">
  import { lighterFetch } from "$lib/api/lighter-fetch.ts";
  import { addQueryArgs, debouncePromise } from "$lib/utils/index.ts";
  import type { LessonParentCourse } from "$types/course.js";

  interface Props {
    url: string;
    multi?: boolean;
    value?: LessonParentCourse[];
    placeholder?: string | null;
  }

  let {
    url,
    multi = true,
    value = $bindable([]),
    placeholder = null,
  }: Props = $props();

  let search = $state("");
  let highIdx = $state(0);
  let opts = $state<LessonParentCourse[]>([]);
  let total = $state(0);
  let cache = new Map();

  let searchInput: HTMLInputElement;

  const tagChosen = (topicId: number): boolean =>
    value.some((c) => c.topics.some((t) => t.ID === topicId));

  const selectTag = (tag: LessonParentCourse, topicId: number) => {
    // NOTE: Propably a neglegable check. Could just push value if not multi.
    const topic = tag.topics.find((t) => t.ID === topicId);
    if (!topic) return;

    if (!multi) {
      value = [{ ...tag, topics: [topic] }];
    }

    const exisistingCourse = value.find((c) => c.course_id === tag.course_id);

    if (exisistingCourse) {
      if (exisistingCourse.topics.some((t) => t.ID === topicId)) return;
      exisistingCourse.topics.push(topic);
    } else {
      value.push({ ...tag, topics: [topic] });
    }
  };

  const removeTag = (topicId: number) => {
    for (let i = value.length - 1; i >= 0; i--) {
      const idx = value[i].topics.findIndex((t) => t.ID === topicId);
      if (idx === -1) continue;

      value[i].topics.splice(idx, 1);
      if (value[i].topics.length === 0) {
        value.splice(i, 1);
      }
      break;
    }
  };

  const findOptByIdx = (
    idx: number,
  ): { course: LessonParentCourse; topicId: number } | null => {
    let curr = 0;
    for (const course of opts) {
      if (course.topics && Array.isArray(course.topics)) {
        for (const topic of course.topics) {
          if (curr === idx) {
            return { course, topicId: topic.ID };
          }
          curr++;
        }
      }
    }

    return null;
  };

  const handleKeyDown = (e: KeyboardEvent) => {
    if (e.key === "Enter") {
      e.preventDefault();
      if (!total || !(e.target as HTMLInputElement).value || !opts.length)
        return;
      const { course, topicId } = findOptByIdx(highIdx);
      if (course) {
        selectTag(course, topicId);
        highIdx = 0;
        search = "";
      }
    } else if (e.key === "Backspace" && !search.length && value.length) {
      value.pop();
    } else if (e.key === "ArrowUp" && highIdx > 0) {
      e.preventDefault();
      highIdx--;
    } else if (e.key === "ArrowDown" && highIdx + 1 < total) {
      e.preventDefault();
      highIdx++;
    }
  };

  const doFetch = async (q = "") => {
    q = q.toLowerCase().trim();

    if (cache.has(q)) {
      const { data, total: cachedTotal } = cache.get(q);
      opts = data;
      total = cachedTotal;
      return Promise.resolve(opts);
    }

    try {
      let request = addQueryArgs(url + encodeURIComponent(q), {
        status: "any",
      });
      const resp = await lighterFetch<LessonParentCourse[]>({
        url: request,
        method: "GET",
        parse: false,
      });

      if (!resp.ok) {
        throw new Error("Error fetching topics");
      }

      const data = await resp.json();
      total = parseInt(resp.headers.get("total-topics"));

      let globalIdx = 0;
      for (const opt of data) {
        if (opt.topics && Array.isArray(opt.topics)) {
          for (const topic of opt.topics) {
            topic._idx = globalIdx++;
          }
        }
      }

      cache.set(q, { data, total });
      opts = data;

      return data;
    } catch {
      opts = [];
      total = 0;
      return [];
    }
  };

  const debouncedFetch = debouncePromise(doFetch, 350);
</script>

<div class="search-container tag-search">
  <div class="search-wrap">
    <div class="selected-tags">
      {#each value as course}
        {#each course.topics as topic}
          <span class="tag">
            {course.course_title} → {topic.title}
            <button
              type="button"
              class="remove-tag"
              onclick={(e) => {
                e.stopPropagation();
                removeTag(topic.ID);
              }}>×</button
            >
          </span>
        {/each}
      {/each}
    </div>

    <input
      type="text"
      class="search"
      placeholder={!value.length ? (placeholder ?? "Add tag") : ""}
      bind:value={search}
      bind:this={searchInput}
      onkeydown={handleKeyDown}
    />
  </div>

  {#if search.trim()}
    <ul class="dropdown">
      {#await debouncedFetch(search) then opts}
        {#each opts as opt}
          <li class="opt-course">
            <b>{opt.course_title}</b>
            <ul>
              {#each opt.topics as topic}
                <li
                  onmousedown={() => selectTag(opt, topic.ID)}
                  role="option"
                  tabindex="0"
                  aria-selected={topic._idx === highIdx}
                  class:hl={topic._idx === highIdx}
                  class:chosen={tagChosen(topic.ID)}
                >
                  {topic.title}
                </li>
              {/each}
            </ul>
          </li>
        {/each}
      {/await}
    </ul>
  {/if}
</div>
