/* @ds-bundle: {"format":4,"namespace":"EmilyDiazSLPDesignSystem_218dc8","components":[{"name":"BulletList","sourcePath":"components/content/BulletList.jsx"},{"name":"CourseCard","sourcePath":"components/content/CourseCard.jsx"},{"name":"QuoteBlock","sourcePath":"components/content/QuoteBlock.jsx"},{"name":"ServiceCard","sourcePath":"components/content/ServiceCard.jsx"},{"name":"Badge","sourcePath":"components/core/Badge.jsx"},{"name":"Button","sourcePath":"components/core/Button.jsx"},{"name":"Card","sourcePath":"components/core/Card.jsx"},{"name":"SectionHeading","sourcePath":"components/core/SectionHeading.jsx"},{"name":"Checkbox","sourcePath":"components/forms/Checkbox.jsx"},{"name":"Input","sourcePath":"components/forms/Input.jsx"},{"name":"Select","sourcePath":"components/forms/Select.jsx"},{"name":"Textarea","sourcePath":"components/forms/Textarea.jsx"}],"sourceHashes":{"components/content/BulletList.jsx":"668a388af269","components/content/CourseCard.jsx":"1dac11206ca1","components/content/QuoteBlock.jsx":"86b4b0cba262","components/content/ServiceCard.jsx":"dec4add7be0d","components/core/Badge.jsx":"100445be076d","components/core/Button.jsx":"8f0088e773df","components/core/Card.jsx":"8c498193ce32","components/core/SectionHeading.jsx":"d14c9eede68d","components/forms/Checkbox.jsx":"51bd888ad94d","components/forms/Input.jsx":"9bfee5708290","components/forms/Select.jsx":"09807be45283","components/forms/Textarea.jsx":"fd721e0afcab","ui_kits/website/AboutScreen.jsx":"1d887bd37b67","ui_kits/website/Chrome.jsx":"6aa82b762e07","ui_kits/website/ContactScreen.jsx":"7842c9475501","ui_kits/website/CoursesScreen.jsx":"4e60c720d0cd","ui_kits/website/HomeScreen.jsx":"200648e318ca","ui_kits/website/ResourcesScreen.jsx":"58525dadbef4","ui_kits/website/ServicesScreen.jsx":"9cd9028a15a5"},"inlinedExternals":[],"unexposedExports":[]} */

(() => {

const __ds_ns = (window.EmilyDiazSLPDesignSystem_218dc8 = window.EmilyDiazSLPDesignSystem_218dc8 || {});

const __ds_scope = {};

(__ds_ns.__errors = __ds_ns.__errors || []);

// components/content/BulletList.jsx
try { (() => {
function BulletList({
  items = [],
  marker = 'dot',
  tone = 'light',
  style,
  ...rest
}) {
  const dark = tone === 'dark';
  const glyph = marker === 'check' ? '✓' : '';
  return React.createElement('ul', {
    style: {
      listStyle: 'none',
      margin: 0,
      padding: 0,
      display: 'flex',
      flexDirection: 'column',
      gap: 'var(--space-3)',
      ...style
    },
    ...rest
  }, items.map((it, i) => React.createElement('li', {
    key: i,
    style: {
      display: 'flex',
      alignItems: 'flex-start',
      gap: 'var(--space-3)',
      fontSize: 'var(--text-body-md)',
      lineHeight: 'var(--leading-normal)',
      color: dark ? 'rgba(255,251,253,.85)' : 'var(--text-body)'
    }
  }, React.createElement('span', {
    'aria-hidden': true,
    style: {
      flex: '0 0 auto',
      marginTop: marker === 'check' ? 0 : 7,
      width: marker === 'check' ? 22 : 8,
      height: marker === 'check' ? 22 : 8,
      borderRadius: 'var(--radius-pill)',
      background: dark ? 'var(--blush-300)' : marker === 'check' ? 'var(--blush-100)' : 'var(--rose-500)',
      color: 'var(--plum-700)',
      display: 'grid',
      placeItems: 'center',
      fontSize: 12,
      fontWeight: 700
    }
  }, glyph), React.createElement('span', null, it))));
}
Object.assign(__ds_scope, { BulletList });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/content/BulletList.jsx", error: String((e && e.message) || e) }); }

// components/content/QuoteBlock.jsx
try { (() => {
function QuoteBlock({
  quote,
  attribution,
  role,
  tone = 'tint',
  style,
  ...rest
}) {
  const dark = tone === 'plum';
  return React.createElement('blockquote', {
    style: {
      margin: 0,
      background: dark ? 'var(--surface-inverse)' : 'var(--surface-tint)',
      borderRadius: 'var(--radius-lg)',
      padding: 'var(--space-7)',
      textAlign: 'center',
      ...style
    },
    ...rest
  }, React.createElement('p', {
    style: {
      fontFamily: 'var(--font-script)',
      fontSize: 'var(--text-script-md)',
      lineHeight: 1.05,
      color: dark ? 'var(--blush-300)' : 'var(--plum-700)',
      margin: '0 0 var(--space-4)'
    }
  }, quote), attribution && React.createElement('footer', {
    style: {
      fontFamily: 'var(--font-sans)',
      fontWeight: 'var(--weight-semibold)',
      fontSize: 'var(--text-body-sm)',
      letterSpacing: 'var(--tracking-eyebrow)',
      textTransform: 'uppercase',
      color: dark ? 'rgba(255,251,253,.75)' : 'var(--rose-500)'
    }
  }, attribution, role && React.createElement('span', {
    style: {
      display: 'block',
      fontWeight: 'var(--weight-regular)',
      letterSpacing: 0,
      textTransform: 'none',
      color: dark ? 'rgba(255,251,253,.6)' : 'var(--text-muted)',
      marginTop: 'var(--space-1)'
    }
  }, role)));
}
Object.assign(__ds_scope, { QuoteBlock });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/content/QuoteBlock.jsx", error: String((e && e.message) || e) }); }

// components/core/Badge.jsx
try { (() => {
const tones = {
  rose: {
    background: 'var(--blush-100)',
    color: 'var(--plum-700)'
  },
  plum: {
    background: 'var(--plum-700)',
    color: 'var(--cream-50)'
  },
  outline: {
    background: 'transparent',
    color: 'var(--rose-500)',
    boxShadow: 'inset 0 0 0 1px var(--blush-300)'
  },
  cream: {
    background: 'var(--cream-50)',
    color: 'var(--rose-500)'
  }
};
function Badge({
  tone = 'rose',
  children,
  style,
  ...rest
}) {
  return React.createElement('span', {
    style: {
      display: 'inline-block',
      fontFamily: 'var(--font-sans)',
      fontWeight: 'var(--weight-semibold)',
      fontSize: 'var(--text-eyebrow)',
      letterSpacing: 'var(--tracking-eyebrow)',
      textTransform: 'uppercase',
      lineHeight: 1,
      padding: '8px 14px',
      borderRadius: 'var(--radius-pill)',
      ...tones[tone],
      ...style
    },
    ...rest
  }, children);
}
Object.assign(__ds_scope, { Badge });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/core/Badge.jsx", error: String((e && e.message) || e) }); }

// components/core/Button.jsx
try { (() => {
const base = {
  display: 'inline-flex',
  alignItems: 'center',
  justifyContent: 'center',
  gap: 'var(--space-2)',
  fontFamily: 'var(--font-sans)',
  fontWeight: 'var(--weight-bold)',
  letterSpacing: 'var(--tracking-nav)',
  textTransform: 'lowercase',
  border: 'var(--border-thick) solid transparent',
  borderRadius: 'var(--radius-pill)',
  cursor: 'pointer',
  textDecoration: 'none',
  transition: 'background var(--dur-base) var(--ease-standard),color var(--dur-base) var(--ease-standard),border-color var(--dur-base) var(--ease-standard),transform var(--dur-fast) var(--ease-standard)'
};
const sizes = {
  sm: {
    fontSize: 'var(--text-body-sm)',
    padding: '10px 20px'
  },
  md: {
    fontSize: 'var(--text-body-md)',
    padding: '14px 30px'
  },
  lg: {
    fontSize: 'var(--text-body-lg)',
    padding: '18px 40px'
  }
};
const variants = {
  primary: {
    background: 'var(--action-primary)',
    color: 'var(--cream-50)',
    borderColor: 'var(--action-primary)'
  },
  secondary: {
    background: 'var(--action-secondary)',
    color: 'var(--white)',
    borderColor: 'var(--action-secondary)'
  },
  outline: {
    background: 'transparent',
    color: 'var(--plum-700)',
    borderColor: 'var(--plum-700)'
  },
  ghost: {
    background: 'transparent',
    color: 'var(--rose-500)',
    borderColor: 'transparent',
    textDecoration: 'underline',
    textUnderlineOffset: '4px'
  },
  onPlum: {
    background: 'var(--blush-300)',
    color: 'var(--plum-900)',
    borderColor: 'var(--blush-300)'
  }
};
const hovers = {
  primary: {
    background: 'var(--action-primary-hover)',
    borderColor: 'var(--action-primary-hover)'
  },
  secondary: {
    background: 'var(--action-secondary-hover)',
    borderColor: 'var(--action-secondary-hover)'
  },
  outline: {
    background: 'var(--plum-700)',
    color: 'var(--cream-50)'
  },
  ghost: {
    color: 'var(--plum-700)'
  },
  onPlum: {
    background: 'var(--white)',
    borderColor: 'var(--white)'
  }
};
function Button({
  variant = 'primary',
  size = 'md',
  href,
  fullWidth = false,
  disabled = false,
  iconLeft,
  iconRight,
  style,
  children,
  ...rest
}) {
  const [hover, setHover] = React.useState(false);
  const s = {
    ...base,
    ...sizes[size],
    ...variants[variant],
    ...(hover && !disabled ? hovers[variant] : null),
    ...(fullWidth ? {
      width: '100%'
    } : null),
    ...(disabled ? {
      opacity: .45,
      cursor: 'not-allowed'
    } : null),
    ...style
  };
  const Tag = href ? 'a' : 'button';
  return React.createElement(Tag, {
    href,
    style: s,
    disabled: Tag === 'button' ? disabled : undefined,
    onMouseEnter: () => setHover(true),
    onMouseLeave: () => setHover(false),
    ...rest
  }, iconLeft, children, iconRight);
}
Object.assign(__ds_scope, { Button });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/core/Button.jsx", error: String((e && e.message) || e) }); }

// components/content/CourseCard.jsx
try { (() => {
function CourseCard({
  kind,
  title,
  body,
  image,
  imageAlt = '',
  ctaLabel = 'Learn More',
  ctaHref,
  layout = 'vertical',
  reverse = false,
  style,
  ...rest
}) {
  const [hover, setHover] = React.useState(false);
  const row = layout === 'horizontal';
  return React.createElement('article', {
    onMouseEnter: () => setHover(true),
    onMouseLeave: () => setHover(false),
    style: {
      display: 'grid',
      gridTemplateColumns: row ? 'minmax(0,1fr) minmax(0,1fr)' : '1fr',
      gap: 0,
      background: 'var(--surface-card)',
      borderRadius: 'var(--radius-lg)',
      overflow: 'hidden',
      boxShadow: hover ? 'var(--shadow-lg)' : 'var(--shadow-sm)',
      transition: 'box-shadow var(--dur-base) var(--ease-standard)',
      ...style
    },
    ...rest
  }, React.createElement('div', {
    style: {
      background: 'var(--surface-tint)',
      minHeight: 200,
      order: row ? reverse ? 0 : 2 : 0,
      overflow: 'hidden'
    }
  }, image && React.createElement('img', {
    src: image,
    alt: imageAlt,
    style: {
      width: '100%',
      height: '100%',
      objectFit: 'cover',
      display: 'block',
      transform: hover ? 'scale(1.03)' : 'none',
      transition: 'transform var(--dur-slow) var(--ease-standard)'
    }
  })), React.createElement('div', {
    style: {
      padding: 'var(--space-6)',
      display: 'flex',
      flexDirection: 'column',
      gap: 'var(--space-3)',
      alignItems: 'flex-start'
    }
  }, kind && React.createElement(__ds_scope.Badge, {
    tone: 'rose'
  }, kind), React.createElement('h3', {
    style: {
      fontFamily: 'var(--font-sans)',
      fontWeight: 'var(--weight-bold)',
      fontSize: 'var(--text-h3)',
      color: 'var(--text-heading)',
      margin: 0
    }
  }, title), React.createElement('p', {
    style: {
      fontSize: 'var(--text-body-md)',
      lineHeight: 'var(--leading-loose)',
      color: 'var(--text-body)',
      margin: 0,
      flex: 1
    }
  }, body), React.createElement(__ds_scope.Button, {
    variant: 'primary',
    size: 'sm',
    href: ctaHref
  }, ctaLabel)));
}
Object.assign(__ds_scope, { CourseCard });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/content/CourseCard.jsx", error: String((e && e.message) || e) }); }

// components/content/ServiceCard.jsx
try { (() => {
function ServiceCard({
  icon,
  title,
  body,
  ctaLabel,
  ctaHref,
  tone = 'white',
  style,
  ...rest
}) {
  const [hover, setHover] = React.useState(false);
  const dark = tone === 'plum';
  return React.createElement('article', {
    onMouseEnter: () => setHover(true),
    onMouseLeave: () => setHover(false),
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 'var(--space-4)',
      background: dark ? 'var(--surface-inverse)' : tone === 'tint' ? 'var(--surface-tint)' : 'var(--surface-card)',
      borderRadius: 'var(--radius-lg)',
      padding: 'var(--space-6)',
      boxShadow: hover ? 'var(--shadow-md)' : 'var(--shadow-sm)',
      transform: hover ? 'translateY(-3px)' : 'none',
      transition: 'box-shadow var(--dur-base) var(--ease-standard),transform var(--dur-base) var(--ease-standard)',
      ...style
    },
    ...rest
  }, icon && React.createElement('div', {
    style: {
      width: 64,
      height: 64,
      borderRadius: 'var(--radius-pill)',
      background: dark ? 'rgba(255,255,255,.12)' : 'var(--blush-100)',
      display: 'grid',
      placeItems: 'center',
      overflow: 'hidden'
    }
  }, icon), React.createElement('h3', {
    style: {
      fontFamily: 'var(--font-sans)',
      fontWeight: 'var(--weight-bold)',
      fontSize: 'var(--text-h4)',
      color: dark ? 'var(--cream-50)' : 'var(--text-heading)',
      margin: 0
    }
  }, title), React.createElement('p', {
    style: {
      fontSize: 'var(--text-body-md)',
      lineHeight: 'var(--leading-loose)',
      color: dark ? 'rgba(255,251,253,.82)' : 'var(--text-body)',
      margin: 0,
      flex: 1
    }
  }, body), ctaLabel && React.createElement(__ds_scope.Button, {
    variant: dark ? 'onPlum' : 'outline',
    size: 'sm',
    href: ctaHref,
    style: {
      alignSelf: 'flex-start'
    }
  }, ctaLabel));
}
Object.assign(__ds_scope, { ServiceCard });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/content/ServiceCard.jsx", error: String((e && e.message) || e) }); }

// components/core/Card.jsx
try { (() => {
const tones = {
  white: {
    background: 'var(--surface-card)',
    color: 'var(--text-body)'
  },
  cream: {
    background: 'var(--cream-50)',
    color: 'var(--text-body)'
  },
  tint: {
    background: 'var(--surface-tint)',
    color: 'var(--plum-900)'
  },
  plum: {
    background: 'var(--surface-inverse)',
    color: 'var(--cream-50)'
  }
};
function Card({
  tone = 'white',
  elevation = 'sm',
  padding = 'var(--space-6)',
  bordered = false,
  radius = 'var(--radius-lg)',
  style,
  children,
  ...rest
}) {
  const shadows = {
    none: 'none',
    xs: 'var(--shadow-xs)',
    sm: 'var(--shadow-sm)',
    md: 'var(--shadow-md)',
    lg: 'var(--shadow-lg)'
  };
  return React.createElement('div', {
    style: {
      borderRadius: radius,
      padding,
      boxShadow: shadows[elevation],
      border: bordered ? 'var(--border-hairline) solid var(--border-soft)' : 'none',
      ...tones[tone],
      ...style
    },
    ...rest
  }, children);
}
Object.assign(__ds_scope, { Card });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/core/Card.jsx", error: String((e && e.message) || e) }); }

// components/core/SectionHeading.jsx
try { (() => {
function SectionHeading({
  eyebrow,
  script,
  title,
  body,
  align = 'left',
  tone = 'light',
  style,
  children,
  ...rest
}) {
  const heading = tone === 'dark' ? 'var(--cream-50)' : 'var(--text-heading)';
  const eye = tone === 'dark' ? 'var(--blush-300)' : 'var(--rose-500)';
  const copy = tone === 'dark' ? 'rgba(255,251,253,.82)' : 'var(--text-body)';
  return React.createElement('header', {
    style: {
      textAlign: align,
      maxWidth: align === 'center' ? 'var(--container-narrow)' : 'none',
      margin: align === 'center' ? '0 auto' : 0,
      ...style
    },
    ...rest
  }, eyebrow && React.createElement('div', {
    style: {
      fontFamily: 'var(--font-sans)',
      fontWeight: 'var(--weight-semibold)',
      fontSize: 'var(--text-eyebrow)',
      letterSpacing: 'var(--tracking-eyebrow)',
      textTransform: 'uppercase',
      color: eye,
      marginBottom: 'var(--space-3)'
    }
  }, eyebrow), script && React.createElement('div', {
    style: {
      fontFamily: 'var(--font-script)',
      fontSize: 'var(--text-script-md)',
      lineHeight: .95,
      color: eye,
      marginBottom: 'var(--space-2)'
    }
  }, script), title && React.createElement('h2', {
    style: {
      fontFamily: 'var(--font-sans)',
      fontWeight: 'var(--weight-bold)',
      fontSize: 'var(--text-h2)',
      lineHeight: 'var(--leading-snug)',
      letterSpacing: 'var(--tracking-tight)',
      color: heading,
      margin: '0 0 var(--space-4)'
    }
  }, title), body && React.createElement('p', {
    style: {
      fontSize: 'var(--text-body-lg)',
      lineHeight: 'var(--leading-loose)',
      color: copy,
      margin: 0,
      maxWidth: '62ch'
    }
  }, body), children);
}
Object.assign(__ds_scope, { SectionHeading });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/core/SectionHeading.jsx", error: String((e && e.message) || e) }); }

// components/forms/Checkbox.jsx
try { (() => {
function Checkbox({
  label,
  checked,
  onChange,
  id,
  style,
  ...rest
}) {
  const fid = id || 'cb-' + (label || 'field').replace(/\W+/g, '-').toLowerCase();
  const [inner, setInner] = React.useState(false);
  const on = checked !== undefined ? checked : inner;
  const toggle = e => {
    if (checked === undefined) setInner(e.target.checked);
    if (onChange) onChange(e);
  };
  return React.createElement('label', {
    htmlFor: fid,
    style: {
      display: 'flex',
      alignItems: 'flex-start',
      gap: 'var(--space-3)',
      cursor: 'pointer',
      fontSize: 'var(--text-body-sm)',
      lineHeight: 'var(--leading-normal)',
      color: 'var(--text-body)',
      ...style
    }
  }, React.createElement('input', {
    id: fid,
    type: 'checkbox',
    checked: on,
    onChange: toggle,
    style: {
      position: 'absolute',
      opacity: 0,
      width: 0,
      height: 0
    },
    ...rest
  }), React.createElement('span', {
    'aria-hidden': true,
    style: {
      flex: '0 0 auto',
      width: 20,
      height: 20,
      marginTop: 2,
      borderRadius: 'var(--radius-sm)',
      border: 'var(--border-thick) solid ' + (on ? 'var(--rose-500)' : 'var(--border-strong)'),
      background: on ? 'var(--rose-500)' : 'var(--white)',
      color: 'var(--white)',
      display: 'grid',
      placeItems: 'center',
      fontSize: 12,
      fontWeight: 700,
      transition: 'background var(--dur-fast) var(--ease-standard)'
    }
  }, on ? '✓' : ''), React.createElement('span', null, label));
}
Object.assign(__ds_scope, { Checkbox });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/forms/Checkbox.jsx", error: String((e && e.message) || e) }); }

// components/forms/Input.jsx
try { (() => {
const fieldStyle = {
  width: '100%',
  boxSizing: 'border-box',
  fontFamily: 'var(--font-sans)',
  fontSize: 'var(--text-body-md)',
  color: 'var(--ink-900)',
  background: 'var(--white)',
  border: 'var(--border-hairline) solid var(--border-soft)',
  borderRadius: 'var(--radius-md)',
  padding: '14px 16px',
  transition: 'border-color var(--dur-base) var(--ease-standard),box-shadow var(--dur-base) var(--ease-standard)',
  outline: 'none'
};
function Input({
  label,
  hint,
  error,
  id,
  type = 'text',
  style,
  ...rest
}) {
  const [focus, setFocus] = React.useState(false);
  const fid = id || 'in-' + (label || 'field').replace(/\W+/g, '-').toLowerCase();
  return React.createElement('label', {
    htmlFor: fid,
    style: {
      display: 'block'
    }
  }, label && React.createElement('span', {
    style: {
      display: 'block',
      fontWeight: 'var(--weight-semibold)',
      fontSize: 'var(--text-body-sm)',
      color: 'var(--plum-700)',
      marginBottom: 'var(--space-2)'
    }
  }, label), React.createElement('input', {
    id: fid,
    type,
    onFocus: () => setFocus(true),
    onBlur: () => setFocus(false),
    style: {
      ...fieldStyle,
      borderColor: error ? 'var(--status-error)' : focus ? 'var(--rose-500)' : 'var(--border-soft)',
      boxShadow: focus ? 'var(--shadow-focus)' : 'none',
      ...style
    },
    ...rest
  }), (hint || error) && React.createElement('span', {
    style: {
      display: 'block',
      fontSize: 'var(--text-micro)',
      marginTop: 'var(--space-2)',
      color: error ? 'var(--status-error)' : 'var(--text-muted)'
    }
  }, error || hint));
}
Object.assign(__ds_scope, { Input });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/forms/Input.jsx", error: String((e && e.message) || e) }); }

// components/forms/Select.jsx
try { (() => {
function Select({
  label,
  hint,
  options = [],
  id,
  style,
  ...rest
}) {
  const fid = id || 'sel-' + (label || 'field').replace(/\W+/g, '-').toLowerCase();
  return React.createElement('label', {
    htmlFor: fid,
    style: {
      display: 'block'
    }
  }, label && React.createElement('span', {
    style: {
      display: 'block',
      fontWeight: 'var(--weight-semibold)',
      fontSize: 'var(--text-body-sm)',
      color: 'var(--plum-700)',
      marginBottom: 'var(--space-2)'
    }
  }, label), React.createElement('select', {
    id: fid,
    style: {
      width: '100%',
      boxSizing: 'border-box',
      appearance: 'none',
      fontFamily: 'var(--font-sans)',
      fontSize: 'var(--text-body-md)',
      color: 'var(--ink-900)',
      background: 'var(--white)',
      border: 'var(--border-hairline) solid var(--border-soft)',
      borderRadius: 'var(--radius-md)',
      padding: '14px 44px 14px 16px',
      outline: 'none',
      backgroundImage: 'linear-gradient(45deg,transparent 50%,var(--rose-500) 50%),linear-gradient(135deg,var(--rose-500) 50%,transparent 50%)',
      backgroundPosition: 'calc(100% - 22px) 24px,calc(100% - 16px) 24px',
      backgroundSize: '6px 6px,6px 6px',
      backgroundRepeat: 'no-repeat',
      ...style
    },
    ...rest
  }, options.map((o, i) => React.createElement('option', {
    key: i,
    value: o.value !== undefined ? o.value : o
  }, o.label !== undefined ? o.label : o))), hint && React.createElement('span', {
    style: {
      display: 'block',
      fontSize: 'var(--text-micro)',
      marginTop: 'var(--space-2)',
      color: 'var(--text-muted)'
    }
  }, hint));
}
Object.assign(__ds_scope, { Select });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/forms/Select.jsx", error: String((e && e.message) || e) }); }

// components/forms/Textarea.jsx
try { (() => {
function Textarea({
  label,
  hint,
  rows = 5,
  id,
  style,
  ...rest
}) {
  const [focus, setFocus] = React.useState(false);
  const fid = id || 'ta-' + (label || 'field').replace(/\W+/g, '-').toLowerCase();
  return React.createElement('label', {
    htmlFor: fid,
    style: {
      display: 'block'
    }
  }, label && React.createElement('span', {
    style: {
      display: 'block',
      fontWeight: 'var(--weight-semibold)',
      fontSize: 'var(--text-body-sm)',
      color: 'var(--plum-700)',
      marginBottom: 'var(--space-2)'
    }
  }, label), React.createElement('textarea', {
    id: fid,
    rows,
    onFocus: () => setFocus(true),
    onBlur: () => setFocus(false),
    style: {
      width: '100%',
      boxSizing: 'border-box',
      fontFamily: 'var(--font-sans)',
      fontSize: 'var(--text-body-md)',
      lineHeight: 'var(--leading-normal)',
      color: 'var(--ink-900)',
      background: 'var(--white)',
      border: 'var(--border-hairline) solid ' + (focus ? 'var(--rose-500)' : 'var(--border-soft)'),
      borderRadius: 'var(--radius-md)',
      padding: '14px 16px',
      boxShadow: focus ? 'var(--shadow-focus)' : 'none',
      outline: 'none',
      resize: 'vertical',
      ...style
    },
    ...rest
  }), hint && React.createElement('span', {
    style: {
      display: 'block',
      fontSize: 'var(--text-micro)',
      marginTop: 'var(--space-2)',
      color: 'var(--text-muted)'
    }
  }, hint));
}
Object.assign(__ds_scope, { Textarea });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/forms/Textarea.jsx", error: String((e && e.message) || e) }); }

// ui_kits/website/AboutScreen.jsx
try { (() => {
const {
  Button,
  BulletList,
  QuoteBlock
} = window.EmilyDiazSLPDesignSystem_218dc8;
function AboutScreen({
  go
}) {
  return /*#__PURE__*/React.createElement("main", null, /*#__PURE__*/React.createElement(Section, {
    tone: "page"
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: '.9fr 1.1fr',
      gap: 'var(--space-8)',
      alignItems: 'center'
    }
  }, /*#__PURE__*/React.createElement("img", {
    src: IMG.aboutEmily,
    alt: "Emily Diaz",
    style: {
      width: '100%',
      borderRadius: 'var(--radius-xl)',
      display: 'block'
    }
  }), /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("h1", {
    style: {
      fontSize: 'var(--text-h2)',
      fontWeight: 700,
      color: 'var(--plum-700)',
      margin: 0,
      lineHeight: 1.25
    }
  }, "I share about what's worked for me because\u2026"), /*#__PURE__*/React.createElement("div", {
    style: {
      fontFamily: 'var(--font-script)',
      fontSize: 'var(--text-script-lg)',
      color: 'var(--rose-500)',
      lineHeight: .95,
      margin: 'var(--space-3) 0 var(--space-5)'
    }
  }, "I believe it can work for you too."), /*#__PURE__*/React.createElement("p", null, "I'm an AAC Specialist, but I have a confession to make. I started working in this field like most SLPs do \u2014 with absolutely no education or training on complex communication needs or assistive technology. In 2015, I took over a caseload of students I had no clue how to treat. I constantly questioned my evaluations, my IEP goals, my interventions, and my knowledge about AAC. I wasn't \"learning on the job\" either."), /*#__PURE__*/React.createElement("p", null, /*#__PURE__*/React.createElement("strong", null, "I was failing."), " From grant writing, to lending libraries, to workshops and everything in between I somehow managed to take control of my caseload and fall in love with AAC."), /*#__PURE__*/React.createElement("p", null, "I know from experience how it feels to know nothing about devices, have zero staff buy-in, and no time to train teachers and paraprofessionals. I also know that it's possible to change and stop letting AAC scare you and stress you out.")))), /*#__PURE__*/React.createElement(Section, {
    tone: "white"
  }, /*#__PURE__*/React.createElement("h3", {
    style: {
      textAlign: 'center',
      fontSize: 'var(--text-h3)',
      fontWeight: 700,
      color: 'var(--plum-700)',
      marginBottom: 'var(--space-6)'
    }
  }, "I've been featured at\u2026"), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'repeat(6,1fr)',
      gap: 'var(--space-6)',
      alignItems: 'center'
    }
  }, IMG.partners.map(p => /*#__PURE__*/React.createElement("img", {
    key: p,
    src: p,
    alt: "",
    style: {
      width: '100%',
      maxHeight: 64,
      objectFit: 'contain',
      filter: 'grayscale(1)',
      opacity: .6
    }
  })))), /*#__PURE__*/React.createElement(Section, {
    tone: "tint"
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: '.8fr 1.2fr',
      gap: 'var(--space-8)',
      alignItems: 'center'
    }
  }, /*#__PURE__*/React.createElement("img", {
    src: IMG.edited,
    alt: "Emily Diaz",
    style: {
      width: '100%',
      display: 'block'
    }
  }), /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("div", {
    className: "eyebrow"
  }, "professional bio"), /*#__PURE__*/React.createElement("p", {
    style: {
      marginTop: 'var(--space-4)',
      color: 'var(--plum-900)'
    }
  }, "Emily Diaz, M.S. CCC-SLP is a certified speech-language pathologist with a passion for empowering individuals to find their voice. With a focus on neurodiversity affirming care and augmentative & alternative communication (AAC), Emily offers valuable insight and support to clinicians, parents, and school districts."), /*#__PURE__*/React.createElement("p", {
    style: {
      color: 'var(--plum-900)'
    }
  }, "Emily's expertise extends to topics like managing AAC in the classroom, the importance of staff buy-in and dynamic coaching methods, and the nuances of gestalt language processing and AAC implementation strategies."), /*#__PURE__*/React.createElement(Button, {
    variant: "primary",
    onClick: () => go('services')
  }, "see the full list of services")))), /*#__PURE__*/React.createElement(Section, {
    tone: "page"
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: '1fr 1fr',
      gap: 'var(--space-8)',
      alignItems: 'center'
    }
  }, /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("div", {
    className: "eyebrow"
  }, "slp, mom, wife & more"), /*#__PURE__*/React.createElement("div", {
    style: {
      fontFamily: 'var(--font-script)',
      fontSize: 'var(--text-script-md)',
      color: 'var(--plum-700)',
      margin: 'var(--space-2) 0 var(--space-5)'
    }
  }, "a little more about Emily"), /*#__PURE__*/React.createElement(BulletList, {
    marker: "check",
    items: ['My kids have an 8 year age gap', 'Coffee order: vanilla almond milk latte, half sweet', 'Most likely to be found roaming Target aisles', 'Big Little League Baseball fan right now', 'I thrive on routines &amp; systems']
  })), /*#__PURE__*/React.createElement(QuoteBlock, {
    quote: "I'm just a human trying to make a connection to other humans.",
    attribution: "Emily Diaz",
    role: "M.S. CCC-SLP, AAC Specialist"
  }))));
}
window.AboutScreen = AboutScreen;
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/website/AboutScreen.jsx", error: String((e && e.message) || e) }); }

// ui_kits/website/Chrome.jsx
try { (() => {
const IMG = {
  "logo": "../../assets/logo-maroon.png",
  "hero": "https://emilydiazslp.com/wp-content/uploads/2026/04/emily-no-bg-white.webp",
  "ipad": "https://emilydiazslp.com/wp-content/uploads/2023/05/ipad-mock-1024x1024.png",
  "pro": "https://emilydiazslp.com/wp-content/uploads/2023/09/emily-professional-scaled.jpg",
  "aboutEmily": "https://emilydiazslp.com/wp-content/uploads/2026/04/about-pg-emily.webp",
  "edited": "https://emilydiazslp.com/wp-content/uploads/2025/03/emily-edited.png",
  "flourish": "https://emilydiazslp.com/wp-content/uploads/2023/05/Asset-4-1.png",
  "hub": "https://emilydiazslp.com/wp-content/uploads/2026/04/emily-no-bg.webp",
  "handbook": "https://emilydiazslp.com/wp-content/uploads/2026/04/handbook-scaled.webp",
  "ipadBig": "https://emilydiazslp.com/wp-content/uploads/2026/04/ipad-big.webp",
  "blouse": "https://emilydiazslp.com/wp-content/uploads/2026/04/emily-purple-blouse.webp",
  "ig": "https://emilydiazslp.com/wp-content/uploads/2023/09/instagram.png",
  "fb": "https://emilydiazslp.com/wp-content/uploads/2023/09/facebook.png",
  "yt": "https://emilydiazslp.com/wp-content/uploads/2023/09/youtube.png",
  "login": "https://emilydiazslp.com/wp-content/uploads/2023/09/login.png",
  "icShopping": "https://emilydiazslp.com/wp-content/uploads/2023/08/shopping.png",
  "icHeart": "https://emilydiazslp.com/wp-content/uploads/2023/08/heart.png",
  "icBag": "https://emilydiazslp.com/wp-content/uploads/2023/08/school-bag.png",
  "icSpeech": "https://emilydiazslp.com/wp-content/uploads/2023/08/speech.png",
  "icInterview": "https://emilydiazslp.com/wp-content/uploads/2023/08/job-interview.png",
  "partners": ["https://emilydiazslp.com/wp-content/uploads/2023/09/NavyBlueStar_Region13Logo-1024x188.png", "https://emilydiazslp.com/wp-content/uploads/2023/09/talkingaac-logo-fullcolor-small_3.png", "https://emilydiazslp.com/wp-content/uploads/2023/09/r17_logo_250.png", "https://emilydiazslp.com/wp-content/uploads/2023/09/aac-in-the-cloud-1024x1024.jpeg", "https://emilydiazslp.com/wp-content/uploads/2023/09/meaningful-speech-1024x576.jpeg", "https://emilydiazslp.com/wp-content/uploads/2023/09/speechtherapypd-logo.png"]
};
const {
  Button
} = window.EmilyDiazSLPDesignSystem_218dc8;
const NAV = [{
  label: 'about me',
  page: 'about'
}, {
  label: 'services',
  page: 'services',
  children: [{
    label: 'aac services',
    page: 'services'
  }, {
    label: 'business coaching',
    page: 'services'
  }]
}, {
  label: 'aac courses + guides',
  page: 'courses'
}, {
  label: 'contact me',
  page: 'contact'
}];
function SiteHeader({
  page,
  go
}) {
  const [open, setOpen] = React.useState(false);
  return /*#__PURE__*/React.createElement("header", {
    style: {
      position: 'sticky',
      top: 0,
      zIndex: 20,
      background: 'var(--white)',
      boxShadow: 'var(--shadow-xs)'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      background: 'var(--plum-700)',
      color: 'var(--cream-50)',
      fontSize: 13,
      letterSpacing: '.12em',
      textTransform: 'uppercase',
      fontWeight: 600,
      textAlign: 'right',
      padding: '10px var(--gutter)'
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      opacity: .85
    }
  }, "member login")), /*#__PURE__*/React.createElement("div", {
    style: {
      maxWidth: 'var(--container-max)',
      margin: '0 auto',
      padding: '18px var(--gutter)',
      display: 'flex',
      alignItems: 'center',
      gap: 'var(--space-6)'
    }
  }, /*#__PURE__*/React.createElement("a", {
    href: "#",
    onClick: e => {
      e.preventDefault();
      go('home');
    },
    style: {
      flex: '0 0 auto'
    }
  }, /*#__PURE__*/React.createElement("img", {
    src: IMG.logo,
    alt: "Emily Diaz, SLP",
    style: {
      width: 150,
      display: 'block'
    }
  })), /*#__PURE__*/React.createElement("nav", {
    style: {
      display: 'flex',
      gap: 'var(--space-6)',
      marginLeft: 'auto',
      alignItems: 'center'
    }
  }, NAV.map(item => /*#__PURE__*/React.createElement("div", {
    key: item.label,
    style: {
      position: 'relative'
    },
    onMouseEnter: () => item.children && setOpen(true),
    onMouseLeave: () => item.children && setOpen(false)
  }, /*#__PURE__*/React.createElement("a", {
    href: "#",
    onClick: e => {
      e.preventDefault();
      go(item.page);
    },
    style: {
      fontWeight: 700,
      fontSize: 15,
      letterSpacing: 'var(--tracking-nav)',
      color: page === item.page ? 'var(--rose-500)' : 'var(--plum-700)'
    }
  }, item.label), item.children && open && /*#__PURE__*/React.createElement("div", {
    style: {
      position: 'absolute',
      top: 'calc(100% + 12px)',
      left: -16,
      background: 'var(--white)',
      boxShadow: 'var(--shadow-md)',
      borderRadius: 'var(--radius-md)',
      padding: 'var(--space-3)',
      display: 'flex',
      flexDirection: 'column',
      gap: 'var(--space-2)',
      minWidth: 200
    }
  }, item.children.map(c => /*#__PURE__*/React.createElement("a", {
    key: c.label,
    href: "#",
    onClick: e => {
      e.preventDefault();
      go(c.page);
      setOpen(false);
    },
    style: {
      fontSize: 14,
      fontWeight: 500,
      color: 'var(--plum-700)',
      padding: '6px 8px'
    }
  }, c.label))))), /*#__PURE__*/React.createElement(Button, {
    size: "sm",
    variant: "secondary",
    onClick: () => go('resources')
  }, "Free AAC Resources"))));
}
function SiteFooter({
  go
}) {
  const links = [['Free AAC Resources', 'resources'], ['About Me', 'about'], ['Services', 'services'], ['aac courses + guides', 'courses'], ['Contact Me', 'contact']];
  return /*#__PURE__*/React.createElement("footer", {
    style: {
      background: 'var(--plum-700)',
      color: 'var(--cream-50)',
      padding: 'var(--space-8) var(--gutter) var(--space-6)'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      maxWidth: 'var(--container-max)',
      margin: '0 auto',
      display: 'grid',
      gridTemplateColumns: '1.1fr 1fr 1fr',
      gap: 'var(--space-7)',
      alignItems: 'start'
    }
  }, /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("img", {
    src: IMG.logo,
    alt: "Emily Diaz, SLP",
    style: {
      width: 170,
      filter: 'brightness(0) invert(1)'
    }
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: 'var(--space-3)',
      marginTop: 'var(--space-4)'
    }
  }, [IMG.ig, IMG.fb, IMG.yt].map(s => /*#__PURE__*/React.createElement("img", {
    key: s,
    src: s,
    alt: "",
    style: {
      width: 30,
      height: 30,
      objectFit: 'contain',
      filter: 'brightness(0) invert(1)',
      opacity: .9
    }
  })))), /*#__PURE__*/React.createElement("nav", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 'var(--space-2)'
    }
  }, links.map(([l, p]) => /*#__PURE__*/React.createElement("a", {
    key: l,
    href: "#",
    onClick: e => {
      e.preventDefault();
      go(p);
    },
    style: {
      color: 'var(--cream-50)',
      fontWeight: 600,
      fontSize: 14
    }
  }, l))), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 'var(--space-3)',
      alignItems: 'flex-start'
    }
  }, /*#__PURE__*/React.createElement("img", {
    src: IMG.login,
    alt: "",
    style: {
      width: 44,
      filter: 'brightness(0) invert(1)'
    }
  }), /*#__PURE__*/React.createElement("a", {
    href: "#",
    onClick: e => e.preventDefault(),
    style: {
      color: 'var(--blush-300)',
      fontWeight: 700,
      fontSize: 18
    }
  }, "Student Login"))), /*#__PURE__*/React.createElement("div", {
    style: {
      maxWidth: 'var(--container-max)',
      margin: 'var(--space-7) auto 0',
      borderTop: '1px solid var(--border-inverse)',
      paddingTop: 'var(--space-4)',
      fontSize: 12,
      opacity: .75,
      display: 'flex',
      gap: 'var(--space-4)',
      flexWrap: 'wrap'
    }
  }, /*#__PURE__*/React.createElement("span", null, "\xA9 2024 Emily Diaz, SLP | All rights Reserved."), /*#__PURE__*/React.createElement("span", null, "Privacy Policy"), /*#__PURE__*/React.createElement("span", null, "Terms of Use"), /*#__PURE__*/React.createElement("span", null, "Website Disclaimer")));
}
function Section({
  tone = 'page',
  children,
  style
}) {
  const bg = {
    page: 'var(--surface-page)',
    white: 'var(--white)',
    tint: 'var(--blush-100)',
    plum: 'var(--plum-700)'
  }[tone];
  return /*#__PURE__*/React.createElement("section", {
    style: {
      background: bg,
      padding: 'var(--section-y) var(--gutter)',
      ...style
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      maxWidth: 'var(--container-max)',
      margin: '0 auto'
    }
  }, children));
}
Object.assign(window, {
  SiteHeader,
  SiteFooter,
  Section,
  IMG
});
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/website/Chrome.jsx", error: String((e && e.message) || e) }); }

// ui_kits/website/ContactScreen.jsx
try { (() => {
const {
  Input,
  Textarea,
  Select,
  Checkbox,
  Button,
  SectionHeading,
  Card
} = window.EmilyDiazSLPDesignSystem_218dc8;
function ContactScreen() {
  const [sent, setSent] = React.useState(false);
  return /*#__PURE__*/React.createElement("main", null, /*#__PURE__*/React.createElement(Section, {
    tone: "page"
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: '1fr 1fr',
      gap: 'var(--space-8)',
      alignItems: 'start'
    }
  }, /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement(SectionHeading, {
    eyebrow: "contact me",
    script: "let's find the right next step.",
    body: "Tell me a little about who you're supporting and I'll point you toward the service, course, or free resource that fits."
  }), /*#__PURE__*/React.createElement("img", {
    src: IMG.flourish,
    alt: "",
    style: {
      width: 140,
      marginTop: 'var(--space-6)',
      opacity: .9
    }
  })), /*#__PURE__*/React.createElement(Card, {
    tone: "white",
    elevation: "md",
    padding: "var(--space-7)"
  }, sent ? /*#__PURE__*/React.createElement("div", {
    style: {
      textAlign: 'center',
      padding: 'var(--space-6) 0'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      fontFamily: 'var(--font-script)',
      fontSize: 'var(--text-script-md)',
      color: 'var(--rose-500)'
    }
  }, "thank you!"), /*#__PURE__*/React.createElement("p", {
    style: {
      marginTop: 'var(--space-3)'
    }
  }, "I'll be in touch within two business days."), /*#__PURE__*/React.createElement(Button, {
    variant: "ghost",
    onClick: () => setSent(false)
  }, "send another message")) : /*#__PURE__*/React.createElement("form", {
    onSubmit: e => {
      e.preventDefault();
      setSent(true);
    },
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 'var(--space-4)'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: '1fr 1fr',
      gap: 'var(--space-4)'
    }
  }, /*#__PURE__*/React.createElement(Input, {
    label: "First name",
    placeholder: "Emily"
  }), /*#__PURE__*/React.createElement(Input, {
    label: "Last name",
    placeholder: "Diaz"
  })), /*#__PURE__*/React.createElement(Input, {
    label: "Email",
    type: "email",
    placeholder: "you@school.org",
    hint: "I'll never share it."
  }), /*#__PURE__*/React.createElement(Select, {
    label: "I am a\u2026",
    options: ['SLP or related professional', 'Parent / caregiver', 'School district', 'Someone else']
  }), /*#__PURE__*/React.createElement(Textarea, {
    label: "How can I help?",
    rows: 4
  }), /*#__PURE__*/React.createElement(Checkbox, {
    label: "Send me free AAC resources by email"
  }), /*#__PURE__*/React.createElement(Button, {
    variant: "primary",
    size: "lg",
    fullWidth: true,
    type: "submit"
  }, "send it over"))))));
}
window.ContactScreen = ContactScreen;
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/website/ContactScreen.jsx", error: String((e && e.message) || e) }); }

// ui_kits/website/CoursesScreen.jsx
try { (() => {
const {
  CourseCard,
  SectionHeading
} = window.EmilyDiazSLPDesignSystem_218dc8;
const COURSES = [{
  kind: 'signature course',
  title: 'The AAC Assessment Hub',
  body: 'The AAC Assessment Hub provides a step-by-step process for conducting assessments from start to finish, along with data sheets, downloads, and more.',
  image: IMG.hub
}, {
  kind: 'pdf download',
  title: 'The AAC Handbook',
  body: 'A must-have digital resource full of simple handouts about a full range of AAC topics that do the explaining for you.',
  image: IMG.handbook
}, {
  kind: 'workshop',
  title: "When AAC Isn't Working",
  body: 'Stop the cycle of AAC abandonment. This new protocol identifies the system leaks and sparks a RESET that leads to successful AAC implementation.',
  image: IMG.ipadBig
}, {
  kind: 'workshop',
  title: 'Introduction to AAC for GLP',
  body: 'A 2 hour training for parents and professionals to expand their knowledge about AAC best practices and how to support GLPs!',
  image: IMG.blouse
}];
function CoursesScreen() {
  return /*#__PURE__*/React.createElement("main", null, /*#__PURE__*/React.createElement(Section, {
    tone: "page"
  }, /*#__PURE__*/React.createElement(SectionHeading, {
    align: "center",
    script: "the fast track to aac confidence starts here."
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 'var(--space-6)',
      marginTop: 'var(--space-7)'
    }
  }, COURSES.map((c, i) => /*#__PURE__*/React.createElement(CourseCard, {
    key: c.title,
    layout: "horizontal",
    kind: c.kind,
    title: c.title,
    body: c.body,
    image: c.image,
    imageAlt: c.title,
    ctaHref: "#",
    reverse: i % 2 === 1
  })))));
}
window.CoursesScreen = CoursesScreen;
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/website/CoursesScreen.jsx", error: String((e && e.message) || e) }); }

// ui_kits/website/HomeScreen.jsx
try { (() => {
const {
  Button,
  SectionHeading,
  ServiceCard,
  QuoteBlock
} = window.EmilyDiazSLPDesignSystem_218dc8;
function HomeScreen({
  go
}) {
  return /*#__PURE__*/React.createElement("main", null, /*#__PURE__*/React.createElement("section", {
    style: {
      background: 'var(--cream-50)',
      padding: 'var(--space-8) var(--gutter) 0',
      overflow: 'hidden'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      maxWidth: 'var(--container-max)',
      margin: '0 auto',
      display: 'grid',
      gridTemplateColumns: '1.05fr .95fr',
      gap: 'var(--space-7)',
      alignItems: 'end'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      paddingBottom: 'var(--space-9)'
    }
  }, /*#__PURE__*/React.createElement("h1", {
    style: {
      fontSize: 'var(--text-display)',
      fontWeight: 700,
      lineHeight: 1.15,
      color: 'var(--plum-700)',
      margin: 0
    }
  }, "Everyone deserves a"), /*#__PURE__*/React.createElement("div", {
    style: {
      fontFamily: 'var(--font-script)',
      fontSize: 'var(--text-script-xl)',
      lineHeight: .85,
      color: 'var(--rose-500)',
      margin: '4px 0 8px'
    }
  }, "voice."), /*#__PURE__*/React.createElement("h2", {
    style: {
      fontSize: 'var(--text-h2)',
      fontWeight: 700,
      color: 'var(--plum-700)',
      margin: '0 0 var(--space-4)'
    }
  }, "We can help find it."), /*#__PURE__*/React.createElement("p", {
    style: {
      fontSize: 'var(--text-body-lg)',
      lineHeight: 'var(--leading-loose)',
      color: 'var(--text-body)',
      maxWidth: '52ch'
    }
  }, "Our practice values neurodiversity affirming services and specializes in providing support for AAC, social communication, & gestalt language processing. Whether it's therapy or consulting, we are committed to making your journey feel more comfortable and helping you feel more confident."), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: 'var(--space-3)',
      flexWrap: 'wrap',
      marginTop: 'var(--space-5)'
    }
  }, /*#__PURE__*/React.createElement(Button, {
    variant: "primary",
    size: "lg",
    onClick: () => go('resources')
  }, "Resources for SLPs"), /*#__PURE__*/React.createElement(Button, {
    variant: "outline",
    size: "lg",
    onClick: () => go('services')
  }, "Services for Families"))), /*#__PURE__*/React.createElement("div", {
    style: {
      position: 'relative'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      position: 'absolute',
      inset: '12% 6% 0 6%',
      background: 'var(--blush-100)',
      borderRadius: 'var(--radius-blob)'
    }
  }), /*#__PURE__*/React.createElement("img", {
    src: IMG.hero,
    alt: "Emily Diaz",
    style: {
      position: 'relative',
      width: '100%',
      display: 'block'
    }
  })))), /*#__PURE__*/React.createElement(Section, {
    tone: "plum"
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: '1fr 1fr',
      gap: 'var(--space-7)',
      alignItems: 'center'
    }
  }, /*#__PURE__*/React.createElement("h2", {
    style: {
      fontSize: 'var(--text-h2)',
      fontWeight: 700,
      color: 'var(--cream-50)',
      margin: 0,
      lineHeight: 1.25
    }
  }, "We know how challenging it can be to navigate unique & complex communication needs."), /*#__PURE__*/React.createElement("p", {
    style: {
      fontSize: 'var(--text-body-lg)',
      lineHeight: 'var(--leading-loose)',
      color: 'rgba(255,251,253,.85)',
      margin: 0
    }
  }, "There's a lot out there when it comes to learning about topics like communication devices, echolalia, and language delays. You could spend hours researching it all yourself and hopping from therapist to therapist\u2026 or you could let us guide you along the way."))), /*#__PURE__*/React.createElement(Section, {
    tone: "page"
  }, /*#__PURE__*/React.createElement(SectionHeading, {
    align: "center",
    eyebrow: "ready to work together?",
    script: "here's how I can help."
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: '.9fr 1.1fr',
      gap: 'var(--space-7)',
      alignItems: 'center',
      marginTop: 'var(--space-7)'
    }
  }, /*#__PURE__*/React.createElement("img", {
    src: IMG.ipad,
    alt: "AAC device mockup",
    style: {
      width: '100%',
      display: 'block'
    }
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 'var(--space-4)'
    }
  }, [['Evaluations & therapy services', 'Our services are focused on supporting adult and pediatric AAC, gestalt language processing, social communication skills, and other speech/language needs like stuttering, speech sound disorders, and language delays.'], ['1:1 Coaching Packages', 'Communication partner interventions are critical when it comes to understanding AAC implementation or the framework of gestalt language processing. By working together 1:1 over 12 weeks, we can help you become a more confident communication partner.'], ['School Contracts + Consulting', 'We provide special education services through school contracts for speech and language services, assistive technology policies, classroom support, team training, and Independent Educational Evaluations (IEE).']].map(([t, b]) => /*#__PURE__*/React.createElement("article", {
    key: t,
    style: {
      background: 'var(--white)',
      borderRadius: 'var(--radius-lg)',
      padding: 'var(--space-5)',
      boxShadow: 'var(--shadow-sm)'
    }
  }, /*#__PURE__*/React.createElement("h3", {
    style: {
      margin: '0 0 var(--space-2)',
      fontSize: 'var(--text-h4)',
      fontWeight: 700,
      color: 'var(--plum-700)'
    }
  }, t), /*#__PURE__*/React.createElement("p", {
    style: {
      margin: '0 0 var(--space-3)',
      fontSize: 'var(--text-body-sm)',
      lineHeight: 'var(--leading-loose)'
    }
  }, b), /*#__PURE__*/React.createElement("a", {
    href: "#",
    onClick: e => {
      e.preventDefault();
      go('services');
    },
    style: {
      fontWeight: 700,
      fontSize: 13,
      letterSpacing: 'var(--tracking-eyebrow)',
      textTransform: 'uppercase'
    }
  }, "learn more")))))), /*#__PURE__*/React.createElement(Section, {
    tone: "tint"
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      textAlign: 'center'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      fontFamily: 'var(--font-script)',
      fontSize: 'var(--text-script-lg)',
      color: 'var(--plum-700)',
      lineHeight: .95
    }
  }, "we're ready and available to help."), /*#__PURE__*/React.createElement("p", {
    style: {
      maxWidth: '60ch',
      margin: 'var(--space-4) auto var(--space-5)',
      fontSize: 'var(--text-body-lg)',
      lineHeight: 'var(--leading-loose)',
      color: 'var(--plum-900)'
    }
  }, "With years of experience, and being a leader in the field, we offer a full range of AAC solutions to fit any need."), /*#__PURE__*/React.createElement(Button, {
    variant: "primary",
    size: "lg",
    onClick: () => go('services')
  }, "See a full list of services"))), /*#__PURE__*/React.createElement(Section, {
    tone: "white"
  }, /*#__PURE__*/React.createElement("h3", {
    style: {
      textAlign: 'center',
      fontSize: 'var(--text-h3)',
      fontWeight: 700,
      color: 'var(--plum-700)',
      marginBottom: 'var(--space-6)'
    }
  }, "I've worked with these incredible organizations"), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'repeat(6,1fr)',
      gap: 'var(--space-6)',
      alignItems: 'center'
    }
  }, IMG.partners.map(p => /*#__PURE__*/React.createElement("img", {
    key: p,
    src: p,
    alt: "",
    style: {
      width: '100%',
      maxHeight: 70,
      objectFit: 'contain',
      filter: 'grayscale(1)',
      opacity: .65
    }
  })))), /*#__PURE__*/React.createElement(Section, {
    tone: "page"
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: '1fr .85fr',
      gap: 'var(--space-8)',
      alignItems: 'center'
    }
  }, /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("div", {
    className: "eyebrow"
  }, "about emily"), /*#__PURE__*/React.createElement("h2", {
    style: {
      fontSize: 'var(--text-h2)',
      fontWeight: 700,
      color: 'var(--plum-700)',
      margin: 'var(--space-3) 0 var(--space-4)',
      lineHeight: 1.25
    }
  }, "\"I'm just a human trying to make a connection to other humans.\""), /*#__PURE__*/React.createElement("p", {
    style: {
      fontSize: 'var(--text-body-lg)',
      lineHeight: 'var(--leading-loose)'
    }
  }, "Hey there, it's Emily! I'm a speech-language pathologist with a passion for complex communicators and a desire to build connection and confidence through AAC. I've worked in public schools, residential treatment centers, and private practice since 2015 with kids and young adults who just want to be loved & heard."), /*#__PURE__*/React.createElement(Button, {
    variant: "secondary",
    onClick: () => go('about')
  }, "More about Emily")), /*#__PURE__*/React.createElement("img", {
    src: IMG.pro,
    alt: "Emily Diaz",
    style: {
      width: '100%',
      borderRadius: 'var(--radius-xl)',
      boxShadow: 'var(--shadow-md)',
      display: 'block'
    }
  }))));
}
window.HomeScreen = HomeScreen;
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/website/HomeScreen.jsx", error: String((e && e.message) || e) }); }

// ui_kits/website/ResourcesScreen.jsx
try { (() => {
const {
  Input,
  Button,
  SectionHeading,
  Card,
  BulletList
} = window.EmilyDiazSLPDesignSystem_218dc8;
function ResourcesScreen() {
  const [done, setDone] = React.useState(false);
  return /*#__PURE__*/React.createElement("main", null, /*#__PURE__*/React.createElement(Section, {
    tone: "tint"
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: '1.05fr .95fr',
      gap: 'var(--space-8)',
      alignItems: 'center'
    }
  }, /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement(SectionHeading, {
    eyebrow: "free aac resources",
    script: "start here, for free.",
    body: "Grab the handouts and data sheets I use every week with SLPs, teachers and families."
  }), /*#__PURE__*/React.createElement(BulletList, {
    marker: "check",
    tone: "light",
    style: {
      marginTop: 'var(--space-5)'
    },
    items: ['AAC implementation checklist', 'Staff buy-in one-pager', 'Gestalt language processing primer']
  })), /*#__PURE__*/React.createElement(Card, {
    tone: "white",
    elevation: "lg",
    padding: "var(--space-7)"
  }, done ? /*#__PURE__*/React.createElement("div", {
    style: {
      textAlign: 'center'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      fontFamily: 'var(--font-script)',
      fontSize: 'var(--text-script-md)',
      color: 'var(--rose-500)'
    }
  }, "check your inbox!"), /*#__PURE__*/React.createElement("p", {
    style: {
      marginTop: 'var(--space-3)'
    }
  }, "Your free resources are on the way.")) : /*#__PURE__*/React.createElement("form", {
    onSubmit: e => {
      e.preventDefault();
      setDone(true);
    },
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 'var(--space-4)'
    }
  }, /*#__PURE__*/React.createElement("h3", {
    style: {
      margin: 0
    }
  }, "Send me the free resources"), /*#__PURE__*/React.createElement(Input, {
    label: "First name",
    placeholder: "Emily"
  }), /*#__PURE__*/React.createElement(Input, {
    label: "Email",
    type: "email",
    placeholder: "you@school.org"
  }), /*#__PURE__*/React.createElement(Button, {
    variant: "secondary",
    size: "lg",
    fullWidth: true,
    type: "submit"
  }, "send them to me"))))));
}
window.ResourcesScreen = ResourcesScreen;
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/website/ResourcesScreen.jsx", error: String((e && e.message) || e) }); }

// ui_kits/website/ServicesScreen.jsx
try { (() => {
const {
  SectionHeading,
  ServiceCard
} = window.EmilyDiazSLPDesignSystem_218dc8;
const SERVICES = [{
  icon: IMG.icShopping,
  title: 'AAC Evaluations',
  body: "Augmentative & Alternative Communication (AAC) can be a game changer in finding your child's voice. This process starts with a 60-minute evaluation to explore different AAC options in a play-based environment. The trial phase comes next — a few options over 4–6 weeks — before helping you navigate the funding process.",
  cta: 'Book a free consult'
}, {
  icon: IMG.icHeart,
  title: 'Speech & Language Therapy',
  body: 'Our therapy services are individualized to meet your unique needs in the areas of AAC, Gestalt Language Processing, and Social Communication. We provide services in-home and virtually in Arizona, Texas, and Indiana.',
  cta: 'request more info'
}, {
  icon: IMG.icBag,
  title: 'School Contracts & Consulting',
  body: 'We contract with schools across the United States to provide training and support communication needs in the classroom setting. Our contracted services range from virtual trainings to in-person therapy & evaluations.',
  cta: 'Request my Pricing Guide'
}, {
  icon: IMG.icSpeech,
  title: 'Professional Development & Speaking',
  body: 'Our live courses focus on equipping SLPs and related professions to confidently support individuals who use AAC and to learn more about gestalt language processing.',
  cta: 'click to Learn more'
}, {
  icon: IMG.icInterview,
  title: '1:1 Coaching & Consultations',
  body: 'Next-level support for professionals and parents looking for individualized guidance with AAC. Choose from a 12-week coaching program or a one-off consultation.',
  cta: 'book a free consult'
}];
function ServicesScreen({
  go
}) {
  return /*#__PURE__*/React.createElement("main", null, /*#__PURE__*/React.createElement(Section, {
    tone: "page"
  }, /*#__PURE__*/React.createElement(SectionHeading, {
    align: "center",
    eyebrow: "how can i help?",
    title: "AAC Services",
    script: "let's tackle aac together"
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'repeat(3,1fr)',
      gap: 'var(--space-5)',
      marginTop: 'var(--space-7)'
    }
  }, SERVICES.map((s, i) => /*#__PURE__*/React.createElement(ServiceCard, {
    key: s.title,
    tone: i === 4 ? 'tint' : 'white',
    icon: /*#__PURE__*/React.createElement("img", {
      src: s.icon,
      alt: "",
      style: {
        width: 40,
        height: 40,
        objectFit: 'contain'
      }
    }),
    title: s.title,
    body: s.body,
    ctaLabel: s.cta
  })))), /*#__PURE__*/React.createElement(Section, {
    tone: "plum"
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      textAlign: 'center'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      fontFamily: 'var(--font-script)',
      fontSize: 'var(--text-script-lg)',
      color: 'var(--blush-300)',
      lineHeight: .95
    }
  }, "not sure where to start?"), /*#__PURE__*/React.createElement("p", {
    style: {
      color: 'rgba(255,251,253,.85)',
      maxWidth: '56ch',
      margin: 'var(--space-4) auto var(--space-5)',
      fontSize: 'var(--text-body-lg)'
    }
  }, "Book a free 15-minute consult and we'll figure out the next right step together."), /*#__PURE__*/React.createElement("a", {
    href: "#",
    onClick: e => {
      e.preventDefault();
      go('contact');
    },
    style: {
      display: 'inline-block',
      background: 'var(--blush-300)',
      color: 'var(--plum-900)',
      fontWeight: 700,
      padding: '18px 40px',
      borderRadius: 'var(--radius-pill)'
    }
  }, "contact me"))));
}
window.ServicesScreen = ServicesScreen;
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/website/ServicesScreen.jsx", error: String((e && e.message) || e) }); }

__ds_ns.BulletList = __ds_scope.BulletList;

__ds_ns.CourseCard = __ds_scope.CourseCard;

__ds_ns.QuoteBlock = __ds_scope.QuoteBlock;

__ds_ns.ServiceCard = __ds_scope.ServiceCard;

__ds_ns.Badge = __ds_scope.Badge;

__ds_ns.Button = __ds_scope.Button;

__ds_ns.Card = __ds_scope.Card;

__ds_ns.SectionHeading = __ds_scope.SectionHeading;

__ds_ns.Checkbox = __ds_scope.Checkbox;

__ds_ns.Input = __ds_scope.Input;

__ds_ns.Select = __ds_scope.Select;

__ds_ns.Textarea = __ds_scope.Textarea;

})();
