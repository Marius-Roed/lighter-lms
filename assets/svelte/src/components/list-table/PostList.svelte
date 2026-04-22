<script lang="ts">
  import PostRow from "./PostRow.svelte";

  interface Props {
    posts: Array<unknown>;
    columns: Object;
    loading: boolean;
  }

  let { posts, columns, loading }: Props = $props();
</script>

{#snippet header(type: string, text: string, withId = false)}
  {#if type === "cb"}
    <td
      id={withId ? type : undefined}
      class="manage-column column-cb check-column"
    >
      <input id="cb-select-all-1" type="checkbox" />
      <label for="cb-select-all-1"
        ><span class="screen-reader-text">{text}</span></label
      >
    </td>
  {:else}
    <th
      scope="col"
      id={withId ? type : undefined}
      class={`mange-column column-${type}`}
    >
      {#if type === "date"}
        Status
      {:else}
        {text}
      {/if}
    </th>
  {/if}
{/snippet}

<table class="wp-list-table widefat fixed striped table-view-list posts">
  <caption class="screen-reader-text">Courses table</caption>
  <thead>
    <tr>
      {#each Object.entries(columns) as [type, text]}
        {@render header(type, text, true)}
      {/each}
    </tr>
  </thead>
  <tbody id="the-list" class={{ loading }}>
    {#each posts as post}
      <PostRow {post} {columns} />
    {/each}
  </tbody>
  {#if posts.length}
    <tfoot>
      <tr>
        {#each Object.entries(columns) as [type, text]}
          {@render header(type, text)}
        {/each}
      </tr>
    </tfoot>
  {/if}
</table>
