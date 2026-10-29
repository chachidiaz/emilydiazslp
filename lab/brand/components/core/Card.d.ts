import * as React from 'react';

/** Soft-cornered surface container. */
export interface CardProps extends React.HTMLAttributes<HTMLDivElement> {
  tone?: 'white' | 'cream' | 'tint' | 'plum';
  elevation?: 'none' | 'xs' | 'sm' | 'md' | 'lg';
  padding?: string;
  bordered?: boolean;
  radius?: string;
  children?: React.ReactNode;
}
export function Card(props: CardProps): JSX.Element;
