import * as React from 'react';

/** Small uppercase label for course type, category or status. */
export interface BadgeProps extends React.HTMLAttributes<HTMLSpanElement> {
  tone?: 'rose' | 'plum' | 'outline' | 'cream';
  children?: React.ReactNode;
}
export function Badge(props: BadgeProps): JSX.Element;
