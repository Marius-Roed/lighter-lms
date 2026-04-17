/**
 * Normalises a string to use for a trie
 */
function normalise(str: string): string {
  return str
    .toLowerCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036F]/g, "");
}

interface TagData {
  count: number;
  description: string;
  id: number;
  name: string;
  slug: string;
  taxonomy: string;
}

interface SearchResult {
  data: TagData;
  distance: number;
}

class TrieNode {
  readonly children: Map<string, TrieNode> = new Map();
  endOfWord: boolean = false;
  data?: TagData;
}

export class FuzzyTrie {
  private readonly root: TrieNode = new TrieNode();

  insert(word: string, data: TagData): void {
    let node = this.root;
    for (let char of normalise(word)) {
      if (!node.children.has(char)) {
        node.children.set(char, new TrieNode());
      }
      node = node.children.get(char)!;
    }

    node.endOfWord = true;
    node.data = data;
  }

  fuzzySearch(
    prefix: string,
    tolerance: number,
    limit: number,
    excluded: number[] = [],
  ): TagData[] {
    const results: SearchResult[] = [];
    const seen = new Set<number>();

    this._search(this.root, "", normalise(prefix), 0, tolerance, results, seen);

    results.sort(
      (a, b) => a.distance - b.distance || b.data.count - a.data.count,
    );

    const excludeSet = new Set(excluded);
    return results
      .filter((r) => !excludeSet.has(r.data.id))
      .slice(0, limit)
      .map((r) => r.data);
  }

  private _search(
    node: TrieNode,
    current: string,
    prefix: string,
    index: number,
    tolerance: number,
    results: SearchResult[],
    seen: Set<number>,
  ): void {
    if (results.length >= 50) return;

    if (index === prefix.length) {
      this._collectSubtree(node, prefix.length - current.length, results, seen);
      return;
    }

    const char = prefix[index];

    // Exact match
    if (node.children.has(char)) {
      this._search(
        node.children.get(char)!,
        current + char,
        prefix,
        index + 1,
        tolerance,
        results,
        seen,
      );
    }

    if (tolerance > 0) {
      for (let [altChar, child] of node.children) {
        if (altChar !== char) {
          this._search(
            child,
            current + altChar,
            prefix,
            index + 1,
            tolerance - 1,
            results,
            seen,
          );
        }

        this._search(
          node,
          current,
          prefix,
          index + 1,
          tolerance - 1,
          results,
          seen,
        );
      }

      this._search(
        node,
        current,
        prefix,
        index + 1,
        tolerance - 1,
        results,
        seen,
      );
    }
  }

  private _collectSubtree(
    node: TrieNode,
    distance: number,
    results: SearchResult[],
    seen: Set<number>,
  ): void {
    if (node.endOfWord && node.data && !seen.has(node.data.id)) {
      seen.add(node.data.id);
      results.push({ data: node.data, distance });
    }
    for (let [_, child] of node.children) {
      this._collectSubtree(child, distance, results, seen);
    }
  }
}
