#!/usr/bin/env python3
"""Concurrent trigger benchmark with an explicit duplicate-idempotency cohort."""
import argparse, concurrent.futures, json, statistics, time, urllib.request, urllib.error, uuid

def one(base, token, workflow, key, n):
    body=json.dumps({'context':{'load_index':n}}).encode(); req=urllib.request.Request(f'{base.rstrip("/")}/api/workflows/{workflow}/trigger',data=body,method='POST',headers={'Accept':'application/json','Content-Type':'application/json','Authorization':f'Bearer {token}','Idempotency-Key':key})
    start=time.perf_counter()
    try:
        with urllib.request.urlopen(req,timeout=30) as r: b=json.load(r); return r.status,(time.perf_counter()-start)*1000,b.get('id')
    except urllib.error.HTTPError as e: return e.code,(time.perf_counter()-start)*1000,None

def main():
    p=argparse.ArgumentParser();p.add_argument('--base-url',default='http://localhost:8000');p.add_argument('--token',required=True);p.add_argument('--workflow-id',required=True);p.add_argument('-n','--requests',type=int,default=1000);p.add_argument('-c','--concurrency',type=int,default=50);p.add_argument('--duplicate-ratio',type=float,default=.1);a=p.parse_args()
    dup=max(0,min(a.requests,int(a.requests*a.duplicate_ratio))); duplicate_key='load-duplicate-'+str(uuid.uuid4()); keys=[duplicate_key]*dup+[f'load-{uuid.uuid4()}' for _ in range(a.requests-dup)]
    start=time.perf_counter()
    with concurrent.futures.ThreadPoolExecutor(max_workers=a.concurrency) as ex: results=list(ex.map(lambda x:one(a.base_url,a.token,a.workflow_id,x[1],x[0]),enumerate(keys)))
    elapsed=time.perf_counter()-start; lat=sorted(r[1] for r in results); ids=[r[2] for r in results[:dup] if r[2]]
    q=lambda p: lat[min(len(lat)-1,int((len(lat)-1)*p))] if lat else 0
    print(json.dumps({'requests':a.requests,'concurrency':a.concurrency,'elapsed_seconds':round(elapsed,3),'accepted_per_second':round(sum(1 for r in results if r[0]==202)/elapsed,2),'status_counts':{str(s):sum(1 for r in results if r[0]==s) for s in sorted(set(r[0] for r in results))},'latency_ms':{'p50':round(q(.5),2),'p95':round(q(.95),2),'max':round(max(lat,default=0),2)},'duplicate_cohort':{'requests':dup,'unique_execution_ids':len(set(ids)),'expected_unique_execution_ids':1 if dup else 0}},indent=2))
if __name__=='__main__':main()
