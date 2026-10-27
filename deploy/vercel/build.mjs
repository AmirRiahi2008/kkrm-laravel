import { mkdir, writeFile } from "node:fs/promises";
import { resolve } from "node:path";

function origin(name) {
    const value = process.env[name]?.trim();
    if (!value) throw new Error(`${name} must be configured in Vercel.`);

    const url = new URL(value);
    if (
        url.protocol !== "https:" ||
        url.username ||
        url.password ||
        url.search ||
        url.hash ||
        url.pathname !== "/" ||
        ["localhost", "127.0.0.1", "0.0.0.0", "[::1]"].includes(url.hostname)
    ) {
        throw new Error(`${name} must be a public HTTPS origin without a path.`);
    }

    return url.origin;
}

const backend = origin("LARAVEL_ORIGIN");
const site = origin("PUBLIC_SITE_URL");

if (backend === site) {
    throw new Error("LARAVEL_ORIGIN must be the Laravel server, separate from the Vercel origin.");
}

const output = resolve(".vercel/output");
await mkdir(resolve(output, "static"), { recursive: true });

const configuration = {
    version: 3,
    routes: [
        {
            src: "/(.*)",
            dest: `${backend}/$1`,
            headers: { "x-vercel-enable-rewrite-caching": "0" },
            transforms: [
                {
                    type: "request.headers",
                    op: "set",
                    target: { key: "x-kkrm-public-origin" },
                    args: site,
                },
            ],
        },
    ],
};

await writeFile(resolve(output, "config.json"), JSON.stringify(configuration, null, 2) + "\n");
process.stdout.write(`Laravel origin configured: ${backend}\n`);
