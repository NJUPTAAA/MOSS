#include<iostream>
using namespace std;

int a[30005],b[30005];
int n,t;

int main()
{
    scanf("%d%d",&n,&t);
    for (int i=1;i<=n-1;i++) scanf("%d",&a[i]);
    b[1]=true;
    for (int i=1;i<=n;i++) if (b[i]) b[i+a[i]]=true;
    if (b[t]) puts("YES");
    else puts("NO");
    return 0;
}