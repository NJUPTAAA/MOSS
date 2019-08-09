#include<iostream>
using namespace std;
int a[30005],b[30005];
int main()
{
    int n,t;
    cin>>n>>t;
    for (int i=1;i<=n-1;i++)
      cin>>a[i];
    b[1]=true;
    for (int i=1;i<=n;i++)
      if (b[i]) b[i+a[i]]=true;
    if (b[t]) cout<<"YES"<<endl;
    else cout<<"NO"<<endl;
    return 0;
}