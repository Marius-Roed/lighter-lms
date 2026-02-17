const { restUrl, nonce: initialNonce, namespace } = LighterLMS;
let nonce = initialNonce;

type LighterFetchResult<T, P extends boolean | undefined> = P extends false ? Response : T;

interface FetchOptions {
    path: string;
    method?: string;
    data?: Record<string, unknown> | object;
    parse?: boolean;
}

export function lighterFetch<T, P extends boolean | undefined = true>(options: FetchOptions): Promise<LighterFetchResult<T, P>>;

export async function lighterFetch<T = unknown>(options: FetchOptions): Promise<T | Response> {
    const { path, method = "GET", data, parse = true } = options;

    const response = await fetch(
        `${restUrl}${namespace}/${path}`,
        {
            method,
            headers: {
                "Content-Type": "application/json",
                "X-WP-Nonce": nonce,
            },
            ...(data ? { body: JSON.stringify(data) } : {}),
        }
    );

    const newNonce = response.headers.get("X-WP-Nonce");
    if (newNonce) nonce = newNonce;

    if (!response.ok) {
        const error = await response.json().catch(() => null);
        throw new Error(
            error?.message ?? `Request failed: ${response.status}`
        );
    }

    return parse ? response.json() : response;
}

