import React from 'react';

const fieldStyle={width:'100%',boxSizing:'border-box',fontFamily:'var(--font-sans)',fontSize:'var(--text-body-md)',color:'var(--ink-900)',background:'var(--white)',border:'var(--border-hairline) solid var(--border-soft)',borderRadius:'var(--radius-md)',padding:'14px 16px',transition:'border-color var(--dur-base) var(--ease-standard),box-shadow var(--dur-base) var(--ease-standard)',outline:'none'};

export function Input({label,hint,error,id,type='text',style,...rest}){
  const [focus,setFocus]=React.useState(false);
  const fid=id||'in-'+(label||'field').replace(/\W+/g,'-').toLowerCase();
  return React.createElement('label',{htmlFor:fid,style:{display:'block'}},
    label&&React.createElement('span',{style:{display:'block',fontWeight:'var(--weight-semibold)',fontSize:'var(--text-body-sm)',color:'var(--plum-700)',marginBottom:'var(--space-2)'}},label),
    React.createElement('input',{id:fid,type,onFocus:()=>setFocus(true),onBlur:()=>setFocus(false),style:{...fieldStyle,borderColor:error?'var(--status-error)':focus?'var(--rose-500)':'var(--border-soft)',boxShadow:focus?'var(--shadow-focus)':'none',...style},...rest}),
    (hint||error)&&React.createElement('span',{style:{display:'block',fontSize:'var(--text-micro)',marginTop:'var(--space-2)',color:error?'var(--status-error)':'var(--text-muted)'}},error||hint));
}
