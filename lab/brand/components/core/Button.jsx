import React from 'react';

const base={display:'inline-flex',alignItems:'center',justifyContent:'center',gap:'var(--space-2)',fontFamily:'var(--font-sans)',fontWeight:'var(--weight-bold)',letterSpacing:'var(--tracking-nav)',textTransform:'lowercase',border:'var(--border-thick) solid transparent',borderRadius:'var(--radius-pill)',cursor:'pointer',textDecoration:'none',transition:'background var(--dur-base) var(--ease-standard),color var(--dur-base) var(--ease-standard),border-color var(--dur-base) var(--ease-standard),transform var(--dur-fast) var(--ease-standard)'};

const sizes={sm:{fontSize:'var(--text-body-sm)',padding:'10px 20px'},md:{fontSize:'var(--text-body-md)',padding:'14px 30px'},lg:{fontSize:'var(--text-body-lg)',padding:'18px 40px'}};

const variants={
  primary:{background:'var(--action-primary)',color:'var(--cream-50)',borderColor:'var(--action-primary)'},
  secondary:{background:'var(--action-secondary)',color:'var(--white)',borderColor:'var(--action-secondary)'},
  outline:{background:'transparent',color:'var(--plum-700)',borderColor:'var(--plum-700)'},
  ghost:{background:'transparent',color:'var(--rose-500)',borderColor:'transparent',textDecoration:'underline',textUnderlineOffset:'4px'},
  onPlum:{background:'var(--blush-300)',color:'var(--plum-900)',borderColor:'var(--blush-300)'}
};

const hovers={
  primary:{background:'var(--action-primary-hover)',borderColor:'var(--action-primary-hover)'},
  secondary:{background:'var(--action-secondary-hover)',borderColor:'var(--action-secondary-hover)'},
  outline:{background:'var(--plum-700)',color:'var(--cream-50)'},
  ghost:{color:'var(--plum-700)'},
  onPlum:{background:'var(--white)',borderColor:'var(--white)'}
};

export function Button({variant='primary',size='md',href,fullWidth=false,disabled=false,iconLeft,iconRight,style,children,...rest}){
  const [hover,setHover]=React.useState(false);
  const s={...base,...sizes[size],...variants[variant],...(hover&&!disabled?hovers[variant]:null),...(fullWidth?{width:'100%'}:null),...(disabled?{opacity:.45,cursor:'not-allowed'}:null),...style};
  const Tag=href?'a':'button';
  return React.createElement(Tag,{href,style:s,disabled:Tag==='button'?disabled:undefined,onMouseEnter:()=>setHover(true),onMouseLeave:()=>setHover(false),...rest},iconLeft,children,iconRight);
}
