#!/usr/bin/env python3
"""Create two portfolio demo workflows through QueueFlow's public control-plane API."""
import argparse, json, urllib.request, urllib.error

def request(base, path, token=None, method='GET', body=None, headers=None):
    data=None if body is None else json.dumps(body).encode()
    h={'Accept':'application/json', **(headers or {})}
    if token: h['Authorization']=f'Bearer {token}'
    if data is not None: h['Content-Type']='application/json'
    req=urllib.request.Request(base.rstrip('/')+'/api'+path,data=data,headers=h,method=method)
    try:
        with urllib.request.urlopen(req) as r: return json.load(r)
    except urllib.error.HTTPError as e:
        raise SystemExit(f'{method} {path} -> {e.code}: {e.read().decode()}')

def main():
    p=argparse.ArgumentParser(); p.add_argument('--base-url',default='http://localhost:8000'); p.add_argument('--email',default='demo@queueflow.test'); p.add_argument('--password',default='password1234'); p.add_argument('--register',action='store_true'); a=p.parse_args()
    if a.register:
        auth=request(a.base_url,'/auth/register',method='POST',body={'name':'QueueFlow Demo','email':a.email,'password':a.password,'organization_name':'QueueFlow Demo Org'})
        org=auth['organization']
    else:
        auth=request(a.base_url,'/auth/login',method='POST',body={'email':a.email,'password':a.password}); org=auth['organizations'][0]
    token=auth['token']
    reliable=request(a.base_url,'/workflows',token,'POST',{'organization_id':org['id'],'name':'Reliable order pipeline','max_concurrent_executions':20,'queue_priority':'high'})
    steps=[
      {'key':'normalize','type':'transform','config':{'operations':[{'op':'set','key':'status','value':'ready'},{'op':'copy','key':'order_id','from':'payload.order_id'}]},'retry':{'max_attempts':3,'mode':'exponential','base_delay_seconds':1,'jitter':True}},
      {'key':'is_ready','type':'conditional','config':{'field':'status','operator':'equals','value':'ready'},'retry':{'max_attempts':1,'mode':'fixed','base_delay_seconds':1,'jitter':False}},
      {'key':'brief_pause','type':'delay','config':{'seconds':1},'retry':{'max_attempts':1,'mode':'fixed','base_delay_seconds':1,'jitter':False}},
      {'key':'result','type':'store_value','config':{'key':'workflow_result','value':'completed'},'retry':{'max_attempts':1,'mode':'fixed','base_delay_seconds':1,'jitter':False}},
    ]
    request(a.base_url,f"/workflows/{reliable['id']}/publish",token,'POST',{'steps':steps})
    hook=request(a.base_url,f"/workflows/{reliable['id']}/webhook",token,'POST')
    request(a.base_url,f"/workflows/{reliable['id']}/schedules",token,'POST',{'cron':'*/15 * * * *','timezone':'UTC'})
    failure=request(a.base_url,'/workflows',token,'POST',{'organization_id':org['id'],'name':'HTTP retry and dead-letter drill','max_concurrent_executions':5,'queue_priority':'default'})
    failure_steps=[
      {'key':'call_unhealthy','type':'http_request','config':{'method':'GET','url':'https://httpbin.org/status/503','requests_per_minute':60,'timeout_seconds':8},'retry':{'max_attempts':3,'mode':'exponential','base_delay_seconds':2,'max_delay_seconds':15,'jitter':False}},
      {'key':'never_runs','type':'store_value','config':{'key':'unexpected','value':True},'retry':{'max_attempts':1,'mode':'fixed','base_delay_seconds':1,'jitter':False}},
    ]
    request(a.base_url,f"/workflows/{failure['id']}/publish",token,'POST',{'steps':failure_steps})
    print(json.dumps({'token':token,'organization_id':org['id'],'reliable_workflow_id':reliable['id'],'webhook':hook,'failure_workflow_id':failure['id']},indent=2))
if __name__=='__main__': main()
