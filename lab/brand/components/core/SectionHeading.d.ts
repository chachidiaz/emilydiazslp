import * as React from 'react';

/**
 * Eyebrow + script line + bold Poppins headline + lead paragraph — the standard section opener.
 */
export interface SectionHeadingProps extends React.HTMLAttributes<HTMLElement> {
  eyebrow?: string;
  /** Handwritten script line, e.g. "let's tackle aac together". */
  script?: string;
  title?: string;
  body?: string;
  align?: 'left' | 'center';
  /** `dark` for use on plum backgrounds. */
  tone?: 'light' | 'dark';
  children?: React.ReactNode;
}
export function SectionHeading(props: SectionHeadingProps): JSX.Element;
