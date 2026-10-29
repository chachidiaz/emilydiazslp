import React from 'react';

export function BulletList({items=[],marker='dot',tone='light',style,...rest}){
  const dark=tone==='dark';
  const glyph=marker==='check'?'✓':'';
  return React.createElement('ul',{style:{listStyle:'none',margin:0,padding:0,display:'flex',flexDirection:'column',gap:'var(--space-3)',...style},...rest},
    items.map((it,i)=>React.createElement('li',{key:i,style:{display:'flex',alignItems:'flex-start',gap:'var(--space-3)',fontSize:'var(--text-body-md)',lineHeight:'var(--leading-normal)',color:dark?'rgba(255,251,253,.85)':'var(--text-body)'}},
      React.createElement('span',{'aria-hidden':true,style:{flex:'0 0 auto',marginTop:marker==='check'?0:7,width:marker==='check'?22:8,height:marker==='check'?22:8,borderRadius:'var(--radius-pill)',background:dark?'var(--blush-300)':marker==='check'?'var(--blush-100)':'var(--rose-500)',color:'var(--plum-700)',display:'grid',placeItems:'center',fontSize:12,fontWeight:700}},glyph),
      React.createElement('span',null,it))));
}
