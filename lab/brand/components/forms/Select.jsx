import React from 'react';

export function Select({label,hint,options=[],id,style,...rest}){
  const fid=id||'sel-'+(label||'field').replace(/\W+/g,'-').toLowerCase();
  return React.createElement('label',{htmlFor:fid,style:{display:'block'}},
    label&&React.createElement('span',{style:{display:'block',fontWeight:'var(--weight-semibold)',fontSize:'var(--text-body-sm)',color:'var(--plum-700)',marginBottom:'var(--space-2)'}},label),
    React.createElement('select',{id:fid,style:{width:'100%',boxSizing:'border-box',appearance:'none',fontFamily:'var(--font-sans)',fontSize:'var(--text-body-md)',color:'var(--ink-900)',background:'var(--white)',border:'var(--border-hairline) solid var(--border-soft)',borderRadius:'var(--radius-md)',padding:'14px 44px 14px 16px',outline:'none',backgroundImage:'linear-gradient(45deg,transparent 50%,var(--rose-500) 50%),linear-gradient(135deg,var(--rose-500) 50%,transparent 50%)',backgroundPosition:'calc(100% - 22px) 24px,calc(100% - 16px) 24px',backgroundSize:'6px 6px,6px 6px',backgroundRepeat:'no-repeat',...style},...rest},
      options.map((o,i)=>React.createElement('option',{key:i,value:o.value!==undefined?o.value:o},o.label!==undefined?o.label:o))),
    hint&&React.createElement('span',{style:{display:'block',fontSize:'var(--text-micro)',marginTop:'var(--space-2)',color:'var(--text-muted)'}},hint));
}
