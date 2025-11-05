<script>
    import PostList from "$components/list-table/PostList.svelte";
    import Navtop from "./Navtop.svelte";
    import Pagination from "$components/Pagination.svelte";
    import { CoursesStore } from "$lib/posts.svelte";
    import { setContext } from "svelte";

    var lighterCourses = window.lighterCourses;

    let courseStore = new CoursesStore(lighterCourses);

    setContext("course-list", courseStore);

    let { actions } = lighterCourses;
</script>

<form action="get" id="posts-filter">
    <input
        type="hidden"
        name="post_status"
        class="post_status_page"
        value="all"
    />
    <input
        type="hidden"
        name="post_type"
        class="post_type_page"
        value="lighter_courses"
    />
    <input type="hidden" id="_wpnonce" name="_wpnonce" value="179d2ab63e" />
    <input
        type="hidden"
        name="_wp_http_referer"
        value="/wp-admin/edit.php?post_type=lighter_courses"
    />
    <Navtop {actions} />
    {#if courseStore.loading}
        <div>Fetching posts</div>
    {:else if courseStore.error}
        <div>There has been an error</div>
    {:else}
        <PostList posts={courseStore.courses} columns={courseStore.columns} />
    {/if}
    {#if courseStore.pagination.totalPages}
        <div class="pagination-wrap">
            {#if courseStore.pagination.totalPages > 1}
                <Pagination
                    bind:currentPage={courseStore.pagination.currentPage}
                    totalPages={courseStore.pagination.totalPages}
                    onPageChange={courseStore.loadPosts}
                />
            {/if}
            <div class="total">
                Total courses ({courseStore.pagination.totalPosts})
            </div>
        </div>
    {/if}
</form>

<div id="ajax-response"></div>
<div class="clear"></div>
