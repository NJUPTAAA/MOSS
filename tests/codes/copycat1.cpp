#include<bits/stdc++.h>

using namespace std;

int coka,juice[30002],water;

int main()
{
    scanf("%d%d",&coka,&water);
    for(int i=1;i<=coka-1;i++)
    {
        scanf("%d",&juice[i]);
        juice[i]+=i;
    }
    int j=1;
    while(juice[j]!=water&&j!=coka)
    {
        j=juice[j];
    }
    if(juice[j]==water) cout<<"YES";
    else cout<<"NO";
    return 0;
}