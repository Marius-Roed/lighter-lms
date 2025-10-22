import { hydrate } from 'svelte'
import Settings from './LessonSettings.svelte';

const origin = window.origin;
const settingsTarget = document.getElementById('lighter-settings-mount');

if (!settingsTarget) {
    throw new Error('Could not mount lighter-lms');
}

const settings = hydrate(Settings, {
    target: settingsTarget,
});

export default settings;
