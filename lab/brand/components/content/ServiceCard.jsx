import React from 'react';
import { Button } from '../core/Button.jsx';

export function ServiceCard({icon,title,body,ctaLabel,ctaHref,tone='white',style,...rest}){
  const [hover,setHover]=React.useState(false);
  const dark=tone==='plum';
  return React.createElement('article',{onMouseEnter:()=>setHover(true),onMouseLeave:()=>setHover(false),style:{display:'flex',flexDirection:'column',gap:'var(--space-4)',background:dark?'var(--surface-inverse)':tone==='tint'?'var(--surface-tint)':'var(--surface-card)',borderRadius:'var(--radius-lg)',padding:'var(--space-6)',boxShadow:hover?'var(--shadow-md)':'var(--shadow-sm)',transform:hover?'translateY(-3px)':'none',transition:'box-shadow var(--dur-base) var(--ease-standard),transform var(--dur-base) var(--ease-standard)',...style},...rest},
    icon&&React.createElement('div',{style:{width:64,height:64,borderRadius:'var(--radius-pill)',background:dark?'rgba(255,255,255,.12)':'var(--blush-100)',display:'grid',placeItems:'center',overflow:'hidden'}},icon),
    React.createElement('h3',{style:{fontFamily:'var(--font-sans)',fontWeight:'var(--weight-bold)',fontSize:'var(--text-h4)',color:dark?'var(--cream-50)':'var(--text-heading)',margin:0}},title),
    React.createElement('p',{style:{fontSize:'var(--text-body-md)',lineHeight:'var(--leading-loose)',color:dark?'rgba(255,251,253,.82)':'var(--text-body)',margin:0,flex:1}},body),
    ctaLabel&&React.createElement(Button,{variant:dark?'onPlum':'outline',size:'sm',href:ctaHref,style:{alignSelf:'flex-start'}},ctaLabel));
}
