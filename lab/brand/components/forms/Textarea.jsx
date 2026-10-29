import React from 'react';

export function Textarea({label,hint,rows=5,id,style,...rest}){
  const [focus,setFocus]=React.useState(false);
  const fid=id||'ta-'+(label||'field').replace(/\W+/g,'-').toLowerCase();
  return React.createElement('label',{htmlFor:fid,style:{display:'block'}},
    label&&React.createElement('span',{style:{display:'block',fontWeight:'var(--weight-semibold)',fontSize:'var(--text-body-sm)',color:'var(--plum-700)',marginBottom:'var(--space-2)'}},label),
    React.createElement('textarea',{id:fid,rows,onFocus:()=>setFocus(true),onBlur:()=>setFocus(false),style:{width:'100%',boxSizing:'border-box',fontFamily:'var(--font-sans)',fontSize:'var(--text-body-md)',lineHeight:'var(--leading-normal)',color:'var(--ink-900)',background:'var(--white)',border:'var(--border-hairline) solid '+(focus?'var(--rose-500)':'var(--border-soft)'),borderRadius:'var(--radius-md)',padding:'14px 16px',boxShadow:focus?'var(--shadow-focus)':'none',outline:'none',resize:'vertical',...style},...rest}),
    hint&&React.createElement('span',{style:{display:'block',fontSize:'var(--text-micro)',marginTop:'var(--space-2)',color:'var(--text-muted)'}},hint));
}
