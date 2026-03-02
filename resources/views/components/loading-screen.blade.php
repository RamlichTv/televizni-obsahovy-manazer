{{--
    Overview:
    This view composes the components > loading-screen interface, arranging reusable components and route-driven actions so the page stays readable while presentation concerns remain separated from backend logic.
--}}

@props([
    'fill' => "#077cb6"
])
<div id="loading-screen">
    <style>
        #loader #loading-logo{
          animation: loader 1.5s infinite cubic-bezier(0.68, -0.55, 0.27, 1.55);
        }

        @keyframes loader {
          50% {
            transform: rotate(180deg);
            scale: 120%
          }

          100% {
            transform: rotate(360deg);
          }
        }
    </style>
    <div id="loader" {{ $attributes->merge(['class' => 'fixed inset-0 z-[9999] flex items-center justify-center bg-white transition-opacity duration-300']) }}>
        <svg id="loading-logo" class="w-28 h-28" version="1.1" viewBox="0 0 108 94" xmlns="http://www.w3.org/2000/svg">
            <g transform="matrix(.69058 0 0 .68847 -3.265 -55.238)">
                <path d="m151.19 148.5a68.267 68.267 0 0 1-68.267 68.267 68.267 68.267 0 0 1-68.267-68.267 68.267 68.267 0 0 1 68.267-68.267 68.267 68.267 0 0 1 68.267 68.267z" fill="{{ $fill }}" stroke-linecap="round" stroke-linejoin="round" stroke-width=".51684"/>
            </g>
            <g transform="matrix(.69058 0 0 .68847 -3.265 -39.192)">
                <rect x="4.7279" y="79.29" width="156.39" height="91.807" ry="3.7065" fill="{{ $fill }}"/>
                <rect x="9.6066" y="84.23" width="146.63" height="81.927" ry="10.031" fill="#e9faff"/>
                <g fill="{{ $fill  }}">
                    <rect x="18.067" y="91.458" width="53.668" height="67.613" ry="11.064"/>
                    <rect transform="rotate(90)" x="91.458" y="-147.77" width="30.564" height="67.613" ry="11.064"/>
                    <rect transform="rotate(90)" x="128.51" y="-147.77" width="30.564" height="67.613" ry="11.064"/>
                </g>
            </g>
        </svg>
    </div>
    <script>
        (() => {
            const loadingScreen = document.getElementById('loading-screen')
            const loader = document.getElementById('loader')

            if (!loadingScreen || !loader || loadingScreen.dataset.initialized === 'true') {
                return
            }

            loadingScreen.dataset.initialized = 'true'

            const hideLoader = () => {
                loader.classList.add('opacity-0', 'pointer-events-none')
                setTimeout(() => loader.classList.add('hidden'), 300)
            }

            const showLoader = () => {
                loader.classList.remove('hidden', 'opacity-0', 'pointer-events-none')
            }

            const shouldHandleLink = (event, link) => {
                if (event.defaultPrevented || event.button !== 0) {
                    return false
                }

                if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return false
                }

                if (link.target && link.target !== '_self') {
                    return false
                }

                if (link.hasAttribute('download')) {
                    return false
                }

                const href = link.getAttribute('href')

                if (!href || href.startsWith('#')) {
                    return false
                }

                const normalizedHref = href.trim().toLowerCase()

                if (
                    normalizedHref.startsWith('javascript:') ||
                    normalizedHref.startsWith('mailto:') ||
                    normalizedHref.startsWith('tel:') ||
                    normalizedHref.startsWith('data:')
                ) {
                    return false
                }

                const linkUrl = new URL(link.href, window.location.href)
                const currentUrl = new URL(window.location.href)

                if (
                    linkUrl.origin === currentUrl.origin &&
                    linkUrl.pathname === currentUrl.pathname &&
                    linkUrl.search === currentUrl.search &&
                    linkUrl.hash
                ) {
                    return false
                }

                return true
            }

            window.addEventListener('load', hideLoader, { once: true })
            window.addEventListener('pageshow', hideLoader)

            document.addEventListener('click', (event) => {
                const link = event.target.closest('a[href]')

                if (!link || !shouldHandleLink(event, link)) {
                    return
                }

                showLoader()
            })

            document.addEventListener('submit', (event) => {
                if (event.defaultPrevented || !(event.target instanceof HTMLFormElement)) {
                    return
                }

                if (event.target.target && event.target.target !== '_self') {
                    return
                }

                showLoader()
            })

            window.addEventListener('beforeunload', showLoader)
        })()
    </script>
</div>
