import * as React from 'react';

/** Script-set pull quote or testimonial panel. */
export interface QuoteBlockProps extends React.HTMLAttributes<HTMLElement> {
  quote?: string;
  attribution?: string;
  role?: string;
  tone?: 'tint' | 'plum';
}
export function QuoteBlock(props: QuoteBlockProps): JSX.Element;
