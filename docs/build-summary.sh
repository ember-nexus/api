#!/bin/sh

find ./dist -type f -exec stat -c '%s %n' {} + | awk '
function hr(b,   u,i){
  split("B K M G T P",u," ")
  i=1
  while(b>=1024 && i<6){b/=1024; i++}
  return (i==1)?sprintf("%d%s",b,u[i]):sprintf("%.1f%s",b,u[i])
}
{
  size=$1
  sub(/.*\//,"",$2)                 # strip directory -> basename
  n=split($2,p,".")
  ext=(n>1)?p[n]:"(none)"           # extension, or (none)
  c[ext]++; sum[ext]+=size
  if(!(ext in mn)||size<mn[ext])mn[ext]=size
  if(size>mx[ext])mx[ext]=size
}
END{
  printf "%-8s %5s %9s %9s %9s %9s\n","EXT","COUNT","TOTAL","MIN","MAX","AVG"
  for(e in c)printf "%-8s %5d %9s %9s %9s %9s\n",e,c[e],hr(sum[e]),hr(mn[e]),hr(mx[e]),hr(sum[e]/c[e])
}'
