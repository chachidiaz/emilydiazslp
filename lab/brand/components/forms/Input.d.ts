import * as React from 'react';

/** Single-line text field with label, hint and error state. */
export interface InputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label?: string;
  hint?: string;
  error?: string;
}
export function Input(props: InputProps): JSX.Element;
