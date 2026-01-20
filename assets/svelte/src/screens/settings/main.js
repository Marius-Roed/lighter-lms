import { hydrate } from 'svelte'
import App from './App.svelte'
import Notifs from '$components/Notifs.svelte';
const target = document.getElementById('lighter-settings-mount');
const notifTarget = document.getElementById('lighter-notifs');
let app, notifs;

if (!target) {
    console.error('Could not mount lighter-lms');
} else {
    app = hydrate(App, {
        target: target,
    });
}

if (!notifTarget) {
    console.error('Could not mount Lighter notifications');
} else {
    notifs = hydrate(Notifs, {
        target: notifTarget,
    });
}

export default { app, notifs };

