import React from 'react';

const tones={
  rose:{background:'var(--blush-100)',color:'var(--plum-700)'},
  plum:{background:'var(--plum-700)',color:'var(--cream-50)'},
  outline:{background:'transparent',color:'var(--rose-500)',boxShadow:'inset 0 0 0 1px var(--blush-300)'},
  cream:{background:'var(--cream-50)',color:'var(--rose-500)'}
};

export function Badge({tone='rose',children,style,...rest}){
  return React.createElement('span',{style:{display:'inline-block',fontFamily:'var(--font-sans)',fontWeight:'var(--weight-semibold)',fontSize:'var(--text-eyebrow)',letterSpacing:'var(--tracking-eyebrow)',textTransform:'uppercase',lineHeight:1,padding:'8px 14px',borderRadius:'var(--radius-pill)',...tones[tone],...style},...rest},children);
}
