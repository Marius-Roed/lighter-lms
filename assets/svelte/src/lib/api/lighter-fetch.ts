const { restUrl, nonce: initialNonce, namespace } = LighterLMS;
let nonce = initialNonce;

export interface TypedResponse<T> extends Response {
  json(): Promise<T>;
  clone(): TypedResponse<T>;
}

interface FetchOptions {
  path?: string;
  url?: string;
  method?: string;
  data?: Record<string, unknown> | object;
}

export function lighterFetch<T>(
  options: FetchOptions & { parse: false },
): Promise<TypedResponse<T>>;

export function lighterFetch<T>(
  options: FetchOptions & { parse: true },
): Promise<T>;

export async function lighterFetch<T>(
  options: FetchOptions & { parse?: boolean },
): Promise<T | TypedResponse<T>> {
  const { path, url, method = "GET", data, parse = true } = options;

  if (!(path || url)) {
    throw new Error("Cannot use ligherFetch without a 'path' or 'url' defined");
  }

  const endpoint = path ? `${restUrl}${namespace}/${path}` : `${restUrl}${url}`;

  const response = await fetch(endpoint, {
    method,
    headers: {
      "Content-Type": "application/json",
      "X-WP-Nonce": nonce,
    },
    ...(data ? { body: JSON.stringify(data) } : {}),
  });

  const newNonce = response.headers.get("X-WP-Nonce");
  if (newNonce) nonce = newNonce;

  if (!response.ok) {
    const error = await response.json().catch((e) => console.error(e));
    throw new Error(error?.message ?? `Request failed: ${response.status}`);
  }

  return parse
    ? ((await response.json()) as T)
    : (response as TypedResponse<T>);
}
