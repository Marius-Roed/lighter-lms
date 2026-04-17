import { hydrate } from "svelte";
import Settings from "./LessonSettings.svelte";

const origin = window.origin;
const settingsTarget = document.getElementById("lighter-settings-mount");

if (!settingsTarget) {
  throw new Error("Could not mount lighter-lms");
}

const settings = hydrate(Settings, {
  target: settingsTarget,
});

export default settings;

const title = document.getElementById("title");
window.addEventListener("message", (/** @type {MessageEvent} */ e) => {
  if (e.data?.type === "LIGHTER_DIALOG_INIT") {
    console.log("starting dialog editor");
  }
});

title?.addEventListener("input", (e) => {
  window.parent?.postMessage(
    { type: "LIGHTER_LESSON_UPDATE", payload: e.target.value },
    window.location.origin,
  );
});
