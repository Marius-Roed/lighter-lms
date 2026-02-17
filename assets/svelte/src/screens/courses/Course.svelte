<script lang="ts">
    import { setContext } from "svelte";
    import TopicComponent from "$components/Topic.svelte";
    import DeleteModal from "$components/DeleteModal.svelte";
    import Icon from "$components/Icon.svelte";
    import EditModal from "$components/EditModal.svelte";
    import type { Course } from "$lib/models/state/course-post.svelte.ts";
    import type { CourseAPI } from "$lib/api/course-api.ts";
    import { Randflake } from "$lib/utils/randflake.ts";
    import type { TopicData } from "$types/course.js";
    import { Topic } from "$lib/models/state/course-topic.svelte.ts";

    let {
        course,
        api,
    }: {
        course: Course;
        api: CourseAPI;
    } = $props();

    setContext("course", () => api);

    let empty = $derived(!course.sortedTopics.length);

    const addTopic = async () => {
        const newTopic: TopicData = {
            key: new Randflake().generate(),
            title: "New topic",
            sort_order: course.sortedTopics.length,
            course: course.id,
        };

        const topic = course.addTopic(newTopic);

        try {
            const created = await api.createTopic(topic);
            const optimistic = course.topics.findIndex(
                (t) => t.key === created.key,
            );

            if (optimistic !== -1) {
                course.topics[optimistic] = new Topic(created);
            }
        } catch {
            course.removeTopic(topic.key);
            // TODO: Toast failure.
        }
    };
</script>

<div class={["lighter-topics-wrap", empty && "empty"]}>
    <div class="lighter-no-topics">
        <h3>This course has no topics yet.</h3>
        <button type="button" class="lighter-btn" onclick={addTopic}>
            Add the first topic
        </button>
    </div>
    <ol class="topics-wrap">
        {#each course.topics as topic (topic.key)}
            <TopicComponent {topic} i={1} />
        {/each}
    </ol>

    <div class="foot">
        <button
            type="button"
            class="lighter-btn transparent"
            onclick={addTopic}
        >
            <Icon name="plus" />
            Add topic
        </button>
    </div>
    <DeleteModal />
    <EditModal />
</div>
