import { hydrate } from 'svelte'
import App from './App.svelte'

const target = document.getElementById('lighter-lms-mount');
const wpData = window.wpData || {};

if (!target) {
  throw new Error('Could not mount lighter-lms');
}
const app = hydrate(App, {
  target: target,
  props: {
    wpData: wpData,
    screen: target.dataset.screen || "dashboard"
  }
});

export default app;
