<!--
@component
- Custom switch component
- Usage: <Switch checked={yourSetting} name="Form name" onLabel="disable me" [offLabel="enable me"] />
-->

<script>
    let {
        checked = $bindable(),
        name,
        onLabel,
        offLabel = null,
        ...props
    } = $props();

    offLabel = offLabel ?? onLabel;
</script>

<label class="lighter-switch">
    <input
        type="checkbox"
        bind:checked
        {name}
        role="switch"
        aria-checked={checked}
        {...props}
    />
    <span class="lighter-slider"></span>
    {checked ? onLabel : offLabel}
</label>
