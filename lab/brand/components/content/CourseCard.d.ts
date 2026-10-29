import * as React from 'react';

/**
 * Course / digital-product card: format badge, title, blurb, image and CTA.
 */
export interface CourseCardProps extends React.HTMLAttributes<HTMLElement> {
  /** Format label — "signature course", "workshop", "pdf download". */
  kind?: string;
  title?: string;
  body?: string;
  image?: string;
  imageAlt?: string;
  ctaLabel?: string;
  ctaHref?: string;
  /** `horizontal` puts the image beside the copy (alternating rows). */
  layout?: 'vertical' | 'horizontal';
  /** Horizontal only — put the image on the left instead of the right. */
  reverse?: boolean;
}
export function CourseCard(props: CourseCardProps): JSX.Element;
