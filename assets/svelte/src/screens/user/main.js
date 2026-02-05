import { hydrate } from "svelte";
import User from "./User.svelte";

const mount = document.getElementById('lighter-course-user-access');

let app;

if (mount) {
    hydrate(User, {
        target: mount
    });
}

export default app;
