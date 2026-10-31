(() => {
  const gsap = window.gsap;
  const ScrollTrigger = window.ScrollTrigger;
  if (!gsap || !ScrollTrigger) return;

  gsap.registerPlugin(ScrollTrigger);
  const motion = gsap.matchMedia();
  motion.add('(prefers-reduced-motion: no-preference)', () => {
    gsap.from('[data-motion="hero"]', {
      autoAlpha: 0,
      y: 16,
      duration: 0.65,
      ease: 'power2.out',
      stagger: 0.12,
      clearProps: 'all',
    });

    gsap.utils.toArray('[data-motion="step"]').forEach((card) => {
      gsap.from(card, {
        autoAlpha: 0,
        y: 18,
        duration: 0.55,
        ease: 'power2.out',
        clearProps: 'all',
        scrollTrigger: { trigger: card, start: 'top 92%', once: true },
      });
    });
  });
})();
