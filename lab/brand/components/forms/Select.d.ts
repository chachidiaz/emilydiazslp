import * as React from 'react';

/** Dropdown field with a rose chevron. */
export interface SelectProps extends React.SelectHTMLAttributes<HTMLSelectElement> {
  label?: string;
  hint?: string;
  /** Strings, or { label, value } pairs. */
  options?: Array<string | { label: string; value: string }>;
}
export function Select(props: SelectProps): JSX.Element;
