#include<bits/stdc++.h>

using namespace std;
int n,a[30005],t;
int main(){
    scanf("%d%d",&n,&t);
    for(int i=1;i<=n-1;i++){
        scanf("%d",&a[i]);
        a[i]+=i;
    }
    int j=1;
    while(a[j]!=t&&j!=n){
        j=a[j];
    }
    if(a[j]==t) cout<<"YES";
    else cout<<"NO";
    return 0;
}