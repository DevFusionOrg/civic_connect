const vIntersect = {
  mounted(el, binding) {
    el.classList.add('opacity-0') // Start hidden
    el.style.transition = 'opacity 0.5s ease-out' // Ensure smooth fade even if motion preset doesn't cover it

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            el.classList.remove('opacity-0')
            // Add the animation class passed as value (e.g., 'motion-preset-slide-up')
            if (binding.value) {
              const classes = binding.value.split(' ')
              el.classList.add(...classes)
            }
            observer.unobserve(el) // Only animate once
          }
        })
      },
      {
        threshold: 0.1, // Trigger when 10% visible
        rootMargin: '50px', // Trigger slightly before
      },
    )

    observer.observe(el)
    el._observer = observer
  },
  unmounted(el) {
    if (el._observer) {
      el._observer.disconnect()
    }
  },
}

export default vIntersect
