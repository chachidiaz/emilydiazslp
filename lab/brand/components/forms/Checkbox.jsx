import React from 'react';

export function Checkbox({label,checked,onChange,id,style,...rest}){
  const fid=id||'cb-'+(label||'field').replace(/\W+/g,'-').toLowerCase();
  const [inner,setInner]=React.useState(false);
  const on=checked!==undefined?checked:inner;
  const toggle=e=>{if(checked===undefined)setInner(e.target.checked);if(onChange)onChange(e);};
  return React.createElement('label',{htmlFor:fid,style:{display:'flex',alignItems:'flex-start',gap:'var(--space-3)',cursor:'pointer',fontSize:'var(--text-body-sm)',lineHeight:'var(--leading-normal)',color:'var(--text-body)',...style}},
    React.createElement('input',{id:fid,type:'checkbox',checked:on,onChange:toggle,style:{position:'absolute',opacity:0,width:0,height:0},...rest}),
    React.createElement('span',{'aria-hidden':true,style:{flex:'0 0 auto',width:20,height:20,marginTop:2,borderRadius:'var(--radius-sm)',border:'var(--border-thick) solid '+(on?'var(--rose-500)':'var(--border-strong)'),background:on?'var(--rose-500)':'var(--white)',color:'var(--white)',display:'grid',placeItems:'center',fontSize:12,fontWeight:700,transition:'background var(--dur-fast) var(--ease-standard)'}},on?'✓':''),
    React.createElement('span',null,label));
}
