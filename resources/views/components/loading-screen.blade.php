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
        window.addEventListener('load', () => {
            const loader = document.getElementById('loader')
            if (loader) {
                loader.classList.add('opacity-0')
                setTimeout(() => loader.remove(), 300)
            }
        })
    </script>
</div>
