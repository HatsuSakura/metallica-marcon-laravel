import {readFile} from 'node:fs/promises'
import {parse,compileScript} from '@vue/compiler-sfc'
const cache = new Map()
const sourceRoot = new URL('../../../resources/js/',import.meta.url)
export const loadVueComponent = async url => (await import(await componentUrl(url))).default
const componentUrl = async url => {
    if (cache.has(url.href)) return cache.get(url.href)
    const promise = (async () => {
        const {descriptor} = parse(await readFile(url,'utf8'))
        let source = compileScript(descriptor,{id:url.pathname,inlineTemplate:true}).content
        const imports = [...source.matchAll(/from\s+(['"])([^'"]+)\1/g)]
        for (const [,quote,specifier] of imports) {
            let target
            if (specifier.startsWith('@/')) target = new URL(specifier.slice(2),sourceRoot)
            else if (specifier.startsWith('.')) target = new URL(specifier,url)
            else { source = source.replace(`from ${quote}${specifier}${quote}`,`from ${JSON.stringify(import.meta.resolve(specifier))}`); continue }
            if (!/\.(vue|js|mjs)$/.test(target.pathname)) target = new URL(target.href+'.js')
            const resolved = target.pathname.endsWith('.vue') ? await componentUrl(target) : target.href
            source = source.replace(`from ${quote}${specifier}${quote}`,`from ${JSON.stringify(resolved)}`)
        }
        return `data:text/javascript;base64,${Buffer.from(source).toString('base64')}`
    })()
    cache.set(url.href,promise)
    return promise
}
