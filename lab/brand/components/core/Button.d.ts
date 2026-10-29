import * as React from 'react';

/**
 * Primary call-to-action button. Pill-shaped, lowercase, Poppins Bold.
 */
export interface ButtonProps extends React.HTMLAttributes<HTMLElement> {
  /** Visual treatment. Use `onPlum` on plum/rose backgrounds. */
  variant?: 'primary' | 'secondary' | 'outline' | 'ghost' | 'onPlum';
  size?: 'sm' | 'md' | 'lg';
  /** Renders an <a> instead of a <button>. */
  href?: string;
  fullWidth?: boolean;
  disabled?: boolean;
  iconLeft?: React.ReactNode;
  iconRight?: React.ReactNode;
  children?: React.ReactNode;
}
export function Button(props: ButtonProps): JSX.Element;
