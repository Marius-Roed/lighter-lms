import { hydrate } from 'svelte'
import App from './App.svelte'

const target = document.getElementById('lighter-lms-mount');

if (!target) {
  throw new Error('Could not mount lighter-lms');
}
const app = hydrate(App, {
  target: target,
});

export default app;
