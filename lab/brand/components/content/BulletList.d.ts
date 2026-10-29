import * as React from 'react';

/** Rose-dot or blush-check list for "a little more about me" and feature lists. */
export interface BulletListProps extends React.HTMLAttributes<HTMLUListElement> {
  items?: React.ReactNode[];
  marker?: 'dot' | 'check';
  tone?: 'light' | 'dark';
}
export function BulletList(props: BulletListProps): JSX.Element;
