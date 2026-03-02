const actions = [];
const filters = [];

/**
 * Registers an action
 *
 * @param {string} hook - Name of the action
 * @param {Function} callback - Callback function which will run.
 * @param {number} [priority=10] - When to run the function. Higher number runs later.
 */
export function addAction(hook: string, callback: Function, priority: number = 10): void {
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
export function doAction(hook: string, ...args: any): void {
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
export function addFilter(hook: string, callback: Function, priority: number = 10): void {
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
export function applyFilter<T>(hook: string, value: T, ...args: any): T {
  return (filters[hook] || []).reduce((v: T, { callback }) => {
    try {
      return callback(v, ...args);
    } catch (e) {
      console.error(`Error in filter "${hook}":`, e);
    }
  }, value);
}

// @ts-ignore
window.LighterLMS = window.LighterLMS || {};
window.LighterLMS.addAction = addAction;
window.LighterLMS.doAction = doAction;
window.LighterLMS.addFilter = addFilter;
window.LighterLMS.applyFilter = applyFilter;
