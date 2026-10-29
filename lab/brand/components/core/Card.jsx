import React from 'react';

const tones={
  white:{background:'var(--surface-card)',color:'var(--text-body)'},
  cream:{background:'var(--cream-50)',color:'var(--text-body)'},
  tint:{background:'var(--surface-tint)',color:'var(--plum-900)'},
  plum:{background:'var(--surface-inverse)',color:'var(--cream-50)'}
};

export function Card({tone='white',elevation='sm',padding='var(--space-6)',bordered=false,radius='var(--radius-lg)',style,children,...rest}){
  const shadows={none:'none',xs:'var(--shadow-xs)',sm:'var(--shadow-sm)',md:'var(--shadow-md)',lg:'var(--shadow-lg)'};
  return React.createElement('div',{style:{borderRadius:radius,padding,boxShadow:shadows[elevation],border:bordered?'var(--border-hairline) solid var(--border-soft)':'none',...tones[tone],...style},...rest},children);
}
