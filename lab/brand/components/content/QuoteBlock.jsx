import React from 'react';

export function QuoteBlock({quote,attribution,role,tone='tint',style,...rest}){
  const dark=tone==='plum';
  return React.createElement('blockquote',{style:{margin:0,background:dark?'var(--surface-inverse)':'var(--surface-tint)',borderRadius:'var(--radius-lg)',padding:'var(--space-7)',textAlign:'center',...style},...rest},
    React.createElement('p',{style:{fontFamily:'var(--font-script)',fontSize:'var(--text-script-md)',lineHeight:1.05,color:dark?'var(--blush-300)':'var(--plum-700)',margin:'0 0 var(--space-4)'}},quote),
    attribution&&React.createElement('footer',{style:{fontFamily:'var(--font-sans)',fontWeight:'var(--weight-semibold)',fontSize:'var(--text-body-sm)',letterSpacing:'var(--tracking-eyebrow)',textTransform:'uppercase',color:dark?'rgba(255,251,253,.75)':'var(--rose-500)'}},attribution,role&&React.createElement('span',{style:{display:'block',fontWeight:'var(--weight-regular)',letterSpacing:0,textTransform:'none',color:dark?'rgba(255,251,253,.6)':'var(--text-muted)',marginTop:'var(--space-1)'}},role)));
}
