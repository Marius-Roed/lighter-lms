<script lang="ts">
  import * as Cells from "./body-row/index.js";

  interface Props {
    post: Object;
    columns: Object;
  }

  let { post, columns }: Props = $props();
</script>

<tr id={`post-${post.id}`} class="lighter-post">
  {#each Object.entries(columns) as [type, text]}
    {#if type === "html"}
      <td>wrong</td>
    {:else if type === "cb"}
      <Cells.CheckCell {post} />
    {:else if type === "date"}
      <Cells.DateCell {post} {text} />
    {:else if type === "tags"}
      <Cells.TagsCell {post} />
    {:else if type === "title"}
      <Cells.TitleCell {post} />
    {:else}
      <td><span aria-hidden="false">—</span></td>
    {/if}
  {/each}
</tr>
