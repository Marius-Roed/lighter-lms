const actions = [];
const filters = [];

/**
 * Registers an action
 *
 * @param {string} hook - Name of the action
 * @param {Function} callback - Callback function which will run.
 * @param {number} [priority=10] - When to run the function. Higher number runs later.
 */
export function addAction(hook, callback, priority = 10) {
  actions[hook] = actions[hook] || [];
  actions[hook].push({ callback, priority });
  actions.sort((a, b) => a.priority - b.priority);
}

/**
 * Run all actions on hook
 *
 * @param {string} hook - The action to run
 * @param {...*} args 
 */
export function doAction(hook, ...args) {
  (actions[hook] || []).forEach(({ callback }) => {
    try {
      callback(...args);
    } catch (e) {
      console.error(`Failed to do action "${hook}":`, e);
    }
  });
}

/**
 * Registers a filter.
 *
 * @param {string} hook - The name of the hook.
 * @param {Function} callback - Callback function to run
 * @param {number} [priority=10] - When to apply the filter. Higher number runs later.
 */
export function addFilter(hook, callback, priority = 10) {
  filters[hook] = filters[hook] || [];
  filters[hook].push({ callback, priority });
  filters.sort((a, b) => a.priority - b.priority);
}

/**
 * Applies all filters
 *
 * @param {string} hook - Name of the filter
 * @param {any} value - The starting value
 * @param {...*} args - Args for the callback
 */
export function applyFilter(hook, value, ...args) {
  return (filters[hook] || []).reduce((v, { callback }) => {
    try {
      return callback(v, ...args);
    } catch (e) {
      console.error(`Error in filter "${hook}":`, e);
    }
  }, value);
}

window.LighterLMS = window.LighterLMS || {};
window.LighterLMS.addAction = addAction;
window.LighterLMS.doAction = doAction;
window.LighterLMS.addFilter = addFilter;
window.LighterLMS.applyFilter = applyFilter;
