import { hydrate } from 'svelte'
import App from './App.svelte'
const target = document.getElementById('lighter-settings-mount');

if (!target) {
    throw new Error('Could not mount lighter-lms');
}

let app = hydrate(App, {
    target: target,
});

export default app;

