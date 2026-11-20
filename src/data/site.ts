// Site-wide settings. The header menu is built from `nav`.
export const site = {
  title: "Emily Diaz, SLP. | AAC Speech Therapist",
  shortTitle: "Emily Diaz, SLP",
  description:
    "Our practice values neurodiversity affirming services and specializes in providing support for AAC, social communication, & gestalt language processing.",
  // Desktop menu. Items with `children` become a dropdown.
  nav: [
    { label: "about me", href: "/about/" },
    {
      label: "services",
      href: "/aac-services/",
      children: [
        { label: "aac services", href: "/aac-services/" },
        { label: "business coaching", href: "/business-coaching/" },
      ],
    },
    { label: "aac courses + guides", href: "/courses/" },
    { label: "contact me", href: "/contact/" },
  ],
  // Mobile (full-screen) menu, same order as the old site.
  mobileNav: [
    { label: "Free AAC Resources", href: "/free-resources/" },
    { label: "About Me", href: "/about/" },
    { label: "Services", href: "/aac-services/" },
    { label: "aac courses + guides", href: "/courses/" },
    { label: "business coaching", href: "/business-coaching/" },
    { label: "Contact Me", href: "/contact/" },
  ],
  freeResources: "/free-resources/",
  memberLogin: "http://learn.emilydiazslp.com/login",
  studentLogin: "https://learn.emilydiazslp.com",
  social: {
    instagram: "http://instagram.com/emilydiazslp",
    facebook: "https://www.facebook.com/emilydiazslp",
    youtube: "https://www.youtube.com/channel/UC3wNhAhCTvW2--ZiEkhKTuQ",
  },
  // Sale bar at the very top. Hidden automatically once `endsAt` has passed.
  saleBar: {
    endsAt: "2026-11-20T17:59:00Z",
    message: "Use code BEFORE20 for 20% off all courses!",
    linkLabel: "SHOP THE SALE →",
    href: "https://lab.emilydiazslp.com/landing-pages/black-friday-2026/",
    image: "/images/webpage-bar-image.webp",
    imageAlt: "the week before Black Friday sale",
  },
};
