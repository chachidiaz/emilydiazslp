import React from 'react';
import { Badge } from '../core/Badge.jsx';
import { Button } from '../core/Button.jsx';

export function CourseCard({kind,title,body,image,imageAlt='',ctaLabel='Learn More',ctaHref,layout='vertical',reverse=false,style,...rest}){
  const [hover,setHover]=React.useState(false);
  const row=layout==='horizontal';
  return React.createElement('article',{onMouseEnter:()=>setHover(true),onMouseLeave:()=>setHover(false),style:{display:'grid',gridTemplateColumns:row?'minmax(0,1fr) minmax(0,1fr)':'1fr',gap:0,background:'var(--surface-card)',borderRadius:'var(--radius-lg)',overflow:'hidden',boxShadow:hover?'var(--shadow-lg)':'var(--shadow-sm)',transition:'box-shadow var(--dur-base) var(--ease-standard)',...style},...rest},
    React.createElement('div',{style:{background:'var(--surface-tint)',minHeight:200,order:row?(reverse?0:2):0,overflow:'hidden'}},
      image&&React.createElement('img',{src:image,alt:imageAlt,style:{width:'100%',height:'100%',objectFit:'cover',display:'block',transform:hover?'scale(1.03)':'none',transition:'transform var(--dur-slow) var(--ease-standard)'}})),
    React.createElement('div',{style:{padding:'var(--space-6)',display:'flex',flexDirection:'column',gap:'var(--space-3)',alignItems:'flex-start'}},
      kind&&React.createElement(Badge,{tone:'rose'},kind),
      React.createElement('h3',{style:{fontFamily:'var(--font-sans)',fontWeight:'var(--weight-bold)',fontSize:'var(--text-h3)',color:'var(--text-heading)',margin:0}},title),
      React.createElement('p',{style:{fontSize:'var(--text-body-md)',lineHeight:'var(--leading-loose)',color:'var(--text-body)',margin:0,flex:1}},body),
      React.createElement(Button,{variant:'primary',size:'sm',href:ctaHref},ctaLabel)));
}
