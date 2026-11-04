interface CloudflareRequestInit extends RequestInit {
  cf?: {
    cacheEverything?: boolean;
    cacheTtl?: number;
    [key: string]: unknown;
  };
}

export default {
  async fetch(request: Request): Promise<Response> {
    const url = new URL(request.url);
    const targetOrigin = 'http://43.161.221.5.sslip.io:8088';
    const targetUrl = new URL(url.pathname + url.search, targetOrigin);

    // Handle WebSocket upgrade transparently
    if (request.headers.get('Upgrade')?.toLowerCase() === 'websocket') {
      const wsHeaders = new Headers(request.headers);
      wsHeaders.set('Host', 'oa.funopc.dev');
      wsHeaders.set('X-Forwarded-Host', 'oa.funopc.dev');
      wsHeaders.set('X-Forwarded-Proto', 'https');
      return fetch(targetUrl.toString(), {
        headers: wsHeaders
      });
    }

    // Build upstream headers
    const newHeaders = new Headers(request.headers);
    const clientIp = request.headers.get('cf-connecting-ip') || '';
    newHeaders.set('Host', 'oa.funopc.dev');
    newHeaders.set('X-Forwarded-Host', 'oa.funopc.dev');
    newHeaders.set('X-Forwarded-Proto', 'https');
    if (clientIp) {
      newHeaders.set('X-Real-IP', clientIp);
      const existingXff = request.headers.get('x-forwarded-for');
      newHeaders.set('X-Forwarded-For', existingXff ? `${existingXff}, ${clientIp}` : clientIp);
    }

    // Intercept JS bundles that trigger doc.tuoluojiang.com background iframe
    if (url.pathname.endsWith('.js') && (url.pathname.includes('chunk-de69c8c8') || url.pathname.includes('app.'))) {
      const resp = await fetch(targetUrl.toString(), { headers: newHeaders });
      let text = await resp.text();
      text = text.replace(/https:\/\/doc\.tuoluojiang\.com\/files\/search[^\"]+/g, 'about:blank');
      text = text.replace(/https:\/\/doc\.tuoluojiang\.com\/forum[^\"]+/g, 'about:blank');
      if (url.pathname.includes('app.')) {
        text = text.replace(
          /t\.formCreateInject\.rule\.effect\.required/g,
          '(t.formCreateInject.rule&&t.formCreateInject.rule.effect&&t.formCreateInject.rule.effect.required)'
        );
        text = text.replace(
          /t\.formCreateInject\.effect\.required/g,
          '(t.formCreateInject.effect&&t.formCreateInject.effect.required)'
        );
      }
      const respHeaders = new Headers(resp.headers);
      respHeaders.delete('content-encoding');
      respHeaders.set('content-type', 'application/javascript; charset=utf-8');
      respHeaders.set('cache-control', 'public, max-age=86400');
      return new Response(text, { status: resp.status, headers: respHeaders });
    }

    const isStatic = /\.(js|css|png|jpg|jpeg|gif|ico|bmp|swf|svg|woff|woff2|ttf|eot|otf|webp|map|json)$/i.test(url.pathname);

    const init: CloudflareRequestInit = {
      method: request.method,
      headers: newHeaders,
      redirect: 'manual'
    };

    if (isStatic && (request.method === 'GET' || request.method === 'HEAD')) {
      init.cf = {
        cacheEverything: true,
        cacheKey: targetUrl.origin + targetUrl.pathname + '?v=20261104-assets',
        cacheTtl: 2592000
      };
    }

    if (request.method !== 'GET' && request.method !== 'HEAD') {
      init.body = request.body;
    }

    try {
      const resp = await fetch(targetUrl.toString(), init);

      // Clone response headers to handle redirect rewrites
      const respHeaders = new Headers(resp.headers);
      const location = respHeaders.get('Location');
      if (location) {
        const rewrittenLocation = location
          .replace(/http:\/\/43\.161\.221\.5(?::8088)?/g, 'https://oa.funopc.dev')
          .replace(/http:\/\/43\.161\.221\.5\.sslip\.io(?::8088)?/g, 'https://oa.funopc.dev')
          .replace(/http:\/\/oa\.funopc\.dev(?::8088)?/g, 'https://oa.funopc.dev');
        respHeaders.set('Location', rewrittenLocation);
      }

      return new Response(resp.body, {
        status: resp.status,
        statusText: resp.statusText,
        headers: respHeaders
      });
    } catch (err: unknown) {
      const message = err instanceof Error ? err.stack || err.message : String(err);
      return new Response('Gateway Error: ' + message, { status: 502 });
    }
  }
};
