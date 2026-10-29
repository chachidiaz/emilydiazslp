import React from 'react';

export function SectionHeading({eyebrow,script,title,body,align='left',tone='light',style,children,...rest}){
  const heading=tone==='dark'?'var(--cream-50)':'var(--text-heading)';
  const eye=tone==='dark'?'var(--blush-300)':'var(--rose-500)';
  const copy=tone==='dark'?'rgba(255,251,253,.82)':'var(--text-body)';
  return React.createElement('header',{style:{textAlign:align,maxWidth:align==='center'?'var(--container-narrow)':'none',margin:align==='center'?'0 auto':0,...style},...rest},
    eyebrow&&React.createElement('div',{style:{fontFamily:'var(--font-sans)',fontWeight:'var(--weight-semibold)',fontSize:'var(--text-eyebrow)',letterSpacing:'var(--tracking-eyebrow)',textTransform:'uppercase',color:eye,marginBottom:'var(--space-3)'}},eyebrow),
    script&&React.createElement('div',{style:{fontFamily:'var(--font-script)',fontSize:'var(--text-script-md)',lineHeight:.95,color:eye,marginBottom:'var(--space-2)'}},script),
    title&&React.createElement('h2',{style:{fontFamily:'var(--font-sans)',fontWeight:'var(--weight-bold)',fontSize:'var(--text-h2)',lineHeight:'var(--leading-snug)',letterSpacing:'var(--tracking-tight)',color:heading,margin:'0 0 var(--space-4)'}},title),
    body&&React.createElement('p',{style:{fontSize:'var(--text-body-lg)',lineHeight:'var(--leading-loose)',color:copy,margin:0,maxWidth:'62ch'}},body),
    children);
}
