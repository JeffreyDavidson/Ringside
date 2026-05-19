import autoprefixer from 'autoprefixer'
import tailwind from '@tailwindcss/postcss'

export default {
    plugins: [autoprefixer, tailwind()],
}
