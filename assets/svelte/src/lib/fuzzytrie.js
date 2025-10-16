/**
 * Normalises a string to use for a trie
 *
 * @param {string} str 
 */
function normalise(str) {
    return str.toLowerCase().normalize("NFD").replace(/[\u0300-\u036F]/g, '');
}

class TrieNode {
    constructor() {
        this.children = new Map();
        this.endOfWord = false;
        this.data = null;
    }
}

export class FuzzyTrie {
    constructor() {
        this.root = new TrieNode();
    }

    /**
     * Inserts a new node in the Trie.
     *
     * @param {string} word 
     * @param {Object} data 
     */
    insert(word, data) {
        const norm = normalise(word)

        let node = this.root;
        for (let char of norm) {
            if (!node.children.has(char)) {
                node.children.set(char, new TrieNode());
            }
            node = node.children.get(char);
        }

        node.endOfWord = true;
        node.data = data;
    }

    /**
     * Fuzzily search for a word.
     *
     * @param {string} prefix Part of the word to search for
     * @param {number} tolerance The amount of chars which can mismatch.
     * @param {number} limit Amount of results to show
     * @param {array} [filtered] An array of options to filter out
     */
    fuzzySearch(prefix, tolerance, limit, filtered = []) {
        const norm = normalise(prefix);
        const results = [];
        const uniqueIds = new Set();
        this._fuzzyFindHelper(this.root, '', norm, 0, tolerance, results, uniqueIds);

        results.sort((a, b) => a.distance - b.distance || a.data.count - b.data.count);

        const excludeIds = new Set(filtered.map(opt => opt.id));
        const filteredResult = results.filter(res => !excludeIds.has(res.data.id));

        return filteredResult.slice(0, limit).map(res => res.data);
    }

    /**
     * Helper function to create the fuzzy array.
     *
     * @param {TrieNode} node 
     * @param {string} current
     * @param {string} prefix 
     * @param {number} index 
     * @param {number} tolerance 
     * @param {Array} results 
     */
    _fuzzyFindHelper(node, current, prefix, index, tolerance, results, uniqueIds) {
        if (results.length >= 50) return;

        if (index === prefix.length) {
            this._collectSubtree(node, results, prefix.length - current.length, uniqueIds);
            return;
        }

        const char = prefix[index];
        if (node.children.has(char)) {
            this._fuzzyFindHelper(node.children.get(char), current + char, prefix, index + 1, tolerance, results, uniqueIds);
        }

        if (tolerance > 0) {
            for (let [altChar, child] of node.children) {
                if (altChar !== char) {
                    this._fuzzyFindHelper(child, current + altChar, prefix, index + 1, tolerance - 1, results, uniqueIds);
                }
            }

            this._fuzzyFindHelper(node, current, prefix, index + 1, tolerance - 1, results, uniqueIds);
            for (let [altChar, child] of node.children) {
                this._fuzzyFindHelper(child, current + altChar, prefix, index, tolerance - 1, results, uniqueIds);
            }
        }
    }

    _collectSubtree(node, results, distance, uniqueIds) {
        if (node.endOfWord && node.data && !uniqueIds.has(node.data.id)) {
            uniqueIds.add(node.data.id);
            results.push({ data: node.data, distance });
        }
        for (let [char, child] of node.children) {
            this._collectSubtree(child, results, distance, uniqueIds);
        }
    }
}
