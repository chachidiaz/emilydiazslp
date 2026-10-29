import * as React from 'react';

/**
 * Icon + title + description + CTA card used across the services page.
 */
export interface ServiceCardProps extends React.HTMLAttributes<HTMLElement> {
  /** Flat brand icon image (an <img>), 40–48px. */
  icon?: React.ReactNode;
  title?: string;
  body?: string;
  ctaLabel?: string;
  ctaHref?: string;
  tone?: 'white' | 'tint' | 'plum';
  children?: React.ReactNode;
}
export function ServiceCard(props: ServiceCardProps): JSX.Element;
