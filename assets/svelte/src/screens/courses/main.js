import { hydrate } from "svelte";
import Course from "./Course.svelte";
import Settings from "./Settings.svelte";

const target = document.getElementById("lighter-course-mount");
const settingsTarget = document.getElementById("lighter-course-settings");

if (!target || !settingsTarget) {
  throw new Error("Could not mount lighter-lms");
}

const course = hydrate(Course, {
  target: target,
});

const settings = hydrate(Settings, {
  target: settingsTarget,
});

export default { course, settings };

// FIX: This hack shouldn't be done. Maybe we use the wp autosave by default
document.getElementById("save-post")?.addEventListener("click", (e) => {
  if (wp?.autosave?.server) {
    wp.autosave.server.postChanged = () => false;
  }

  wpCookies?.set?.("wp-saving-post", "saved");
});
