import * as React from 'react';

/** Consent / multi-select checkbox. Uncontrolled unless `checked` is passed. */
export interface CheckboxProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label?: React.ReactNode;
  checked?: boolean;
}
export function Checkbox(props: CheckboxProps): JSX.Element;
